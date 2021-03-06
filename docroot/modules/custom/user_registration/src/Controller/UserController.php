<?php

namespace Drupal\user_registration\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for user routes.
 */
class UserController extends ControllerBase {


  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function resetPassLogin($uid, $timestamp, $hash) {
    // The current user is not logged in, so check the parameters.
    $current = REQUEST_TIME;
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // No time out for first time login.
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      drupal_set_message($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'error');
      return $this->redirect('user.pass');
    }
    elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
      $last_login_time = $user->getLastLoginTime();

      user_login_finalize($user);
      $this->logger->notice('User %name used one-time login link at time %timestamp.', [
        '%name' => $user->getDisplayName(),
        '%timestamp' => $timestamp
      ]);
      drupal_set_message($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
      // Let the user's password be changed without the current password
      // check.
      $token = Crypt::randomBytesBase64(55);
      $_SESSION['pass_reset_' . $user->id()] = $token;
      return $this->redirect(
        // @TODO Add more complicated condition here.
        (time() - $last_login_time > 100 && $last_login_time != 0) ? 'entity.user.edit_form' : 'page_manager.page_view_my_settings',
        ['user' => $user->id()],
        [
          'query' => ['pass-reset-token' => $token, 'redirected-first-login' => $last_login_time == 0],
          'absolute' => TRUE,
        ]
      );
    }

    drupal_set_message($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
    return $this->redirect('user.pass');
  }

  /**
   * Toggles Useful Information user block.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Current user object.
   * @param mixed $display
   *   State of the Profile useful information block.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toggleUsefulInfo(AccountInterface $user, $display = NULL) {
    if ($this->currentUser()->id() !== $user->id()) {
      throw new AccessDeniedHttpException();
    }

    $response = new AjaxResponse();

    /** @var \Drupal\user_registration\CollapsibleBlockService $collapsible_block */
    $collapsible_block = \Drupal::service('user_registration.collapsible_block');

    // Load "Profile useful information" block and toggle its display.
    if ($block = BlockContent::load(45)) {
      $collapsible_block->toggle($block, $display);
      $response->setData(['collapsed' => $collapsible_block->isCollapsed($block)]);
    }

    return $response;
  }

}
