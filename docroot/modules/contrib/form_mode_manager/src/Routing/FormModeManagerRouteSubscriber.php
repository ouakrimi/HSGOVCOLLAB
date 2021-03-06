<?php

namespace Drupal\form_mode_manager\Routing;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class FormModeManagerRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityTypeManager = $entity_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityDisplayRepository->getAllFormModes() as $entity_type_id => $display_modes) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      unset($display_modes['register']);
      foreach ($display_modes as $machine_name => $display_mode) {
        if ('user' != $entity_type_id && $route = $this->getFormModeManagerListPage($collection, $entity_type, $display_mode, $machine_name)) {
          // Add entity type list page specific to form_modes.
          $collection->add("form_mode_manager.$machine_name.add_page", $route);
        }
        // Edit routes.
        if ($route = $this->getFormModeManagerEditRoute($collection, $entity_type, $display_mode, $machine_name)) {
          $collection->add("entity." . $display_mode['id'], $route);
        }
        // All compatible entity without FILE because multiStep,
        // edit form or use FileEntity module.
        switch ($entity_type_id) {
          case 'file':
            break;

          default:
            $route = $this->getFormModeManagerAddRoute($collection, $entity_type, $display_mode, $machine_name);
            if ($route) {
              $collection->add("entity.add." . $machine_name, $route);
            }
            break;
        }
        // Specific case with user entity (add operation).
        if ('user' === $entity_type_id && $route = $this->getFormModeManagerUserAddRoute($entity_type, $display_mode)) {
          $collection->add($display_mode['id'], $route);
        }
        if ('user' === $entity_type_id && $route = $this->getFormModeManagerUserAddAdmin($entity_type, $display_mode, $machine_name)) {
          $collection->add("admin.{$display_mode['id']}", $route);
        }
      }
    }
  }

  /**
   * Gets entity edit route with specific form_display.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple possibly dynamic entity types.
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerEditRoute(RouteCollection $collection, EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ($form_mode_manager_path = $entity_type->getLinkTemplate($machine_name)) {
      $entity_id = $entity_type->id();

      if (in_array($entity_id, ['taxonomy_term', 'contact_form'])) {
        $route = $collection->get("entity.{$entity_id}.edit_form");
      }
      else {
        $route = new Route($form_mode_manager_path);
      }

      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Edit as @label', ['@label' => $form_display['label']])->render(),
        ])
        ->addRequirements([
          '_entity_access' => "$entity_id.update",
          '_permission' => "use {$form_display['id']} form mode",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
        ]);

      return $route;
    }
    return NULL;
  }

  /**
   * Gets entity add operation routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerAddRoute(RouteCollection $collection, EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ($entity_type->getFormClass($machine_name)) {
      $entity_type_id = $entity_type->id();
      $route = new Route("/$entity_type_id/add/" . "{entity_bundle_id}" . "/{form_display}");
      $this->setFormModeManagerDefaultsRouteOptions($route, $entity_type, $form_display['id']);

      // Add feature to restrict access on default form_display.
      switch ($entity_type_id) {
        case 'node':
        case 'block_content':
          // node.add route alter.
          $collection_route_name = $entity_type_id . '.add';
          // Special case block_content entities are not standard.
          if ('block_content' === $entity_type_id) {
            $collection_route_name = $entity_type_id . '.add_form';
          }
          $route_add = $collection->get($collection_route_name);
          $route_add->addDefaults(['entity_type' => $entity_type_id]);
          $route_add->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          // node.add_page route alter.
          $route_list = $collection->get("$entity_type_id.add_page");
          $route_list->addDefaults(['entity_type' => $entity_type_id]);
          $route_list->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          // entity.node.edit_form route alter.
          $route_edit_form = $collection->get("entity.$entity_type_id.edit_form");
          $route_edit_form->addDefaults(['entity_type' => $entity_type_id]);
          $route_edit_form->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          break;

        case 'user':
          $route_add = $collection->get("user.register");
          $route_add->addDefaults(['entity_type' => $entity_type_id]);
          $route_add->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          break;
      }
      return $route;
    }
    return FALSE;
  }

  /**
   * Set specific options of NODE routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param string $access
   *   The access routes options for entities.
   */
  protected function setNodeRouteOptions(Route $route, $access) {
    $route->setRequirement('_node_add_access', $access)
      ->setOptions([
        '_node_operation_route' => TRUE,
        'parameters' => [
          'node_type' => [
            'with_config_overrides' => TRUE,
          ],
        ],
      ]);
  }

  /**
   * Set cross entities options into routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param string $access
   *   The access routes options for entities.
   */
  protected function setFormModeManagerRouteOptions(Route $route, $access) {
    $route->setRequirement('_entity_create_access', $access)
      ->setOption('_admin_route', TRUE);
  }

  /**
   * Set default routes option onto Form Mode Manager routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param string $form_display_id
   *   The machine name of current form_display.
   */
  protected function setFormModeManagerDefaultsRouteOptions(Route $route, EntityTypeInterface $entity_type, $form_display_id) {
    $access = (!empty($entity_type->getBundleEntityType())) ? "{$entity_type->id()}:{entity_bundle_id}" : $entity_type->id();

    $route
      ->addDefaults([
        '_entity_form' => $form_display_id,
        '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::entityAdd',
        '_title_callback' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPageTitle',
        'entity_type' => $entity_type,
      ])
      ->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');

    if ('node' === $entity_type->id()) {
      $this->setNodeRouteOptions($route, $access);
    }
    else {
      $this->setFormModeManagerRouteOptions($route, $access);
    }

    $parameters = $route->getOption('parameters') ?: [];
    $parameters += ['form_display' => ['type' => $form_display_id]];

    $route->setOption('parameters', $parameters);
  }

  /**
   * Gets entity create user routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddRoute(EntityTypeInterface $entity_type, $form_display) {
    if ('user' === $entity_type->id()) {
      $route = new Route("/{$entity_type->id()}/register/{form_display}");
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Create new account')->render(),
        ])
        ->addRequirements([
          '_access_user_register' => 'TRUE',
          '_permission' => "use {$form_display['id']} form mode",
        ])
        ->setOptions([
          'parameters' => [
            'form_display' => [
              'type' => $form_display['id'],
            ],
          ],
        ]);
      return $route;
    }
    return FALSE;
  }

  /**
   * Gets entity create user routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddAdmin(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ('user' === $entity_type->id()) {
      $route = new Route("/admin/people/create/{form_display}");
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Add user as @label', ['@label' => $form_display['label']])->render(),
        ])
        ->addRequirements([
          '_permission' => "use {$form_display['id']} form mode",
        ])
        ->setOptions([
          'parameters' => [
            'form_display' => [
              'type' => $form_display['id'],
            ],
          ],
        ]);
      return $route;
    }
    return FALSE;
  }

  /**
   * Gets entity add operation routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerListPage($collection, EntityTypeInterface $entity_type, $form_display, $machine_name) {
    $entity_id = $entity_type->id();
    $route = new Route("/$entity_id/add-list/$machine_name");
    if ('user' === $entity_id) {
      $route = $collection->get("user.admin_create");
      $route->setPath("{$route->getPath()}/$machine_name");
    }
    $route
      ->addDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPage',
        '_title' => t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])->render(),
        'entity_type' => $entity_type,
        'form_display' => $machine_name,
      ])
      ->addRequirements([
        '_permission' => "use {$form_display['id']} form mode",
      ])
      ->setOptions([
        '_admin_route' => TRUE,
      ]);

    return $route;
  }

}
