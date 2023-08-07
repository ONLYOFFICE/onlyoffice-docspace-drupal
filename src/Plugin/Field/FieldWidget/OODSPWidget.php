<?php

namespace Drupal\onlyoffice_docspace\Plugin\Field\FieldWidget;

/**
 * Copyright (c) Ascensio System SIA 2023.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'onlyoffice_docspace_widget' widget.
 *
 * @FieldWidget(
 *   id = "onlyoffice_docspace_widget",
 *   label = @Translation("ONLYOFFICE DocSpace widget"),
 *   field_types = {
 *     "onlyoffice_docspace"
 *   }
 * )
 */
class OODSPWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $element['#attached'] = [
      'library' => ['onlyoffice_docspace/onlyoffice_docspace.widget'],
    ];

    $element['target_id'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->target_id ?? '',
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->type ?? '',
    ];

    $element['title'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->title ?? '',
    ];

    $element['image'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->image ?? '',
    ];

    $element['fields'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['oodsp-fields'],
      ],
    ];

    $element['fields']['image'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $items[$delta]->image ?? '',
        'width' => '100',
        'height' => '100',
        'class' => ['oodsp-image'],
      ],
      '#weight' => -12,
    ];

    $element['fields']['title'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Title'),
      '#default_value' => $items[$delta]->title ?? '',
      '#maxlength' => 1024,
      '#weight' => -11,
      '#wrapper_attributes' => [
        'id' => 'title',
        'class' => ['oodsp-container-inline'],
      ],
    ];

    $element['fields']['remove'] = [
      '#type' => 'button',
      '#value' => $this->t('Remove'),
      '#attributes' => [
        'class' => ['oodsp-remove-button'],
      ],
    ];

    $element['buttons'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['oodsp-fields'],
      ],
    ];

    $element['buttons']['select_room'] = [
      '#type' => 'button',
      '#value' => $this->t('Select room'),
      '#attributes' => [
        'class' => ['oodsp-select-button'],
        'data-mode' => 'room-selector',
        'data-title' => $this->t('Select room'),
      ],
    ];

    $element['buttons']['select_file'] = [
      '#type' => 'button',
      '#value' => $this->t('Select file'),
      '#attributes' => [
        'class' => ['oodsp-select-button'],
        'data-mode' => 'file-selector',
        'data-title' => $this->t('Select file'),
      ],
    ];

    if (isset($items[$delta]->target_id)) {
      $element['buttons']['#attributes']['class'][] = 'hidden';
    }
    else {
      $element['fields']['#attributes']['class'][] = 'hidden';
    }

    return $element;
  }

}
