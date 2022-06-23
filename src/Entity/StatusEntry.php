<?php

namespace Drupal\kulana\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the StatusEntry entity.
 *
 * @ContentEntityType(
 *   id = "status_entry",
 *   label = @Translation("Status entry"),
 *   base_table = "kulana_status_entry",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "created" = "created",
 *     "url" = "url",
 *     "status" = "status",
 *     "time" = "time",
 *     "content_length" = "content_length",
 *     "destination" = "destination",
 *   }
 * )
 */
class StatusEntry extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the status entry.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the status entry.'))
      ->setReadOnly(TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the entity was created.'));
    $fields['url'] = BaseFieldDefinition::create('string');
    $fields['status'] = BaseFieldDefinition::create('integer');
    $fields['time'] = BaseFieldDefinition::create('float');
    $fields['content_length'] = BaseFieldDefinition::create('integer');
    $fields['destination'] = BaseFieldDefinition::create('string');

    return $fields;
  }

}
