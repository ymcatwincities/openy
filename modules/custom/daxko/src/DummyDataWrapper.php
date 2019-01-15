<?php

namespace Drupal\daxko;

/**
 * Class DummyDataWrapper.
 *
 * Provides example of membership matrix.
 */
class DummyDataWrapper extends DataWrapperBase {

  /**
   * {@inheritdoc}
   */
  public function getMembershipPriceMatrix() {
    $matrix = [
      [
        'id' => 'youth',
        'title' => 'Youth',
        'description' => 'Youth membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 10,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 20,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 30,
          ],
        ],
      ],
      [
        'id' => 'adult',
        'title' => 'Adult',
        'description' => 'Adult membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 100,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 200,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 300,
          ],
        ],
      ],
      [
        'id' => 'family',
        'title' => 'Family',
        'description' => 'Family membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 1000,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 2000,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 3000,
          ],
        ],
      ],
    ];

    return $matrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchPins() {
    return [
      [
        'lat' => 49.969547,
        'lng' => 33.607193,
        'title' => 'Location #1 (dummY)',
        'markup' => '<p><strong>Markup</strong> here for location #1</p>',
      ],
      [
        'lat' => 49.968147,
        'lng' => 33.608649,
        'title' => 'Location #2',
        'markup' => '<p><strong>Markup</strong> here for location #2</p>',
      ],
      [
        'lat' => 49.965815,
        'lng' => 33.611157,
        'title' => 'Location #3',
        'markup' => '<p><strong>Markup</strong> here for location #3</p>',
      ],
    ];
  }

}
