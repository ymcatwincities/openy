<?php
namespace Drupal\Tests\migrate_source_csv\Unit;

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Base class for CSV unit tests.
 *
 * @group migrate_source_csv
 */
abstract class CSVUnitBase extends UnitTestCase {

  /**
   * The happy path file url.
   *
   * @var string
   */
  protected $happyPath;

  /**
   * The un-happy path file url.
   *
   * @var string
   */
  protected $sad;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $root_dir = vfsStream::setup('root');
    $happy = <<<'EOD'
id,first_name,last_name,email,country,ip_address
1,Justin,Dean,jdean0@example.com,Indonesia,60.242.130.40
2,Joan,Jordan,jjordan1@example.com,Thailand,137.230.209.171
3,William,Ray,wray2@example.com,Germany,4.75.251.71
4,Jack,Collins,jcollins3@example.com,Indonesia,118.241.243.64
5,Jean,Moreno,jmoreno4@example.com,Portugal,12.24.215.20
6,Dennis,Mitchell,dmitchell5@example.com,Mexico,185.24.131.116
7,Harry,West,hwest6@example.com,Uzbekistan,101.74.110.171
8,Rebecca,Hunt,rhunt7@example.com,France,253.107.6.23
9,Rose,Rogers,rrogers8@example.com,China,21.2.126.228
10,Juan,Walker,jwalker9@example.com,Angola,192.118.77.225
11,Lois,Price,lpricea@example.com,Greece,231.185.100.19
12,Patricia,Bell,pbellb@example.com,Sweden,226.2.254.94
13,Gerald,Kelly,gkellyc@example.com,China,31.204.2.163
14,Kimberly,Jackson,kjacksond@example.com,Thailand,19.187.65.116
15,Jason,Mason,jmasone@example.com,Greece,225.129.68.203
EOD;
    $sad = <<<'EOD'
1|%Justin%|Dean|jdean0@example.com|Indonesia|60.242.130.40
2|Joan|Jordan|jjordan1@example.com|Thailand|137.230.209.171
3|William|Ray|wray2@example.com|Germany|4.75.251.71
4|Jack|Collins|jcollins3@example.com|Indonesia|118.241.243.64
5|Jean|Moreno|jmoreno4@example.com|Portugal|12.24.215.20
6|Dennis|Mitchell|dmitchell5@example.com|Mexico|185.24.131.116
7|Harry|West|hwest6@example.com|Uzbekistan|101.74.110.171
8|Rebecca|Hunt|rhunt7@example.com|France|253.107.6.23
9|Rose|Rogers|rrogers8@example.com|China|21.2.126.228
10|Juan|Walker|jwalker9@example.com|Angola|192.118.77.225
11|Lois|Price|lpricea@example.com|Greece|231.185.100.19
12|Patricia|Bell|pbellb@example.com|Sweden|226.2.254.94
13|Gerald|Kelly|gkellyc@example.com|China|31.204.2.163
14|Kimberly|Jackson|kjacksond@example.com|Thailand|19.187.65.116
15|Jason|Mason|jmasone@example.com|Greece|225.129.68.203

EOD;

    $this->happyPath = vfsStream::newFile('data.csv')
      ->at($root_dir)
      ->withContent($happy)
      ->url();
    $this->sad = vfsStream::newFile('data_edge_case.csv')
      ->at($root_dir)
      ->withContent($sad)
      ->url();

  }
}
