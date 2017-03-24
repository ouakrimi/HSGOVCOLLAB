<?php

/**
 * @file
 * Contains \Drupal\blocktabs\Form\BlocktabsFormBase.
 */

namespace Drupal\blocktabs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for blocktabs add and edit forms.
 */
abstract class BlocktabsFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\blocktabs\BlockTabsInterface
   */
  protected $entity;

  /**
   * The block tabs entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a base class for blocktabs add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The block tabs entity storage.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('blocktabs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Blocktabs name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => array($this->entityStorage, 'load'),
      ),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    );
    $form['event'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an event'),
      '#default_value' => $this->entity->getEvent(),
      '#options' => [
        'click' => $this->t('OnClick'),
        'mouseover' => $this->t('Hover')
      ],
    ];


    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->urlInfo('edit-form'));
  }

}
