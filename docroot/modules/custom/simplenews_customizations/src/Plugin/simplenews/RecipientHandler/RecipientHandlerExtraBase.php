<?php

namespace Drupal\simplenews_customizations\Plugin\simplenews\RecipientHandler;

use Drupal\simplenews\Plugin\simplenews\RecipientHandler\RecipientHandlerBase;
use Drupal\simplenews\SubscriberInterface;

/**
 * Base class for all Recipient Handler classes.
 *
 * This handler sends a newsletter issue to all subscribers of a given
 * newsletter.
 *
 * @RecipientHandler(
 *   id = "simplenews_all",
 *   title = @Translation("All newsletter subscribers"),
 *   types = {
 *     "default"
 *   }
 * )
 */
class RecipientHandlerExtraBase extends RecipientHandlerBase {

  /**
   * Implements SimplenewsRecipientHandlerInterface::buildRecipientQuery()
   */
  public function buildRecipientQuery($newsletter_id = 'default') {
    $select = db_select('simplenews_subscriber', 's');
    $select->innerJoin('simplenews_subscriber__subscriptions', 't', 's.id = t.entity_id');
    $select->addField('s', 'id', 'snid');
    $select->addField('s', 'mail');
    $select->addField('t', 'subscriptions_target_id', 'newsletter_id');
    $select->condition('t.subscriptions_target_id', $newsletter_id);
    $select->condition('t.subscriptions_status', SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED);
    $select->condition('s.status', SubscriberInterface::ACTIVE);

    return $select;
  }

  /**
   *
   */
  public function settingsForm($element, $settings, $form_state) {
    // Add some text to describe the send situation.
    $subscriber_count = $this->count([]);
    $element['count'] = [
      '#type' => 'item',
      '#markup' => t('Send newsletter issue to @count subscribers.', ['@count' => $subscriber_count]),
      '#parents' => ['recipient_handler_settings'],
      '#prefix' => '<div id="recipient-handler-count">',
      '#suffix' => '</div>',
    ];
    return $element;
  }

}
