<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class opOpenSocialPlugin13_addColumnToApplication extends Doctrine_Migration_Base
{
  public function up()
  {
    $this->addColumn('application', 'links', 'array', '', array(
         ));
  }

  public function down()
  {
    $this->removeColumn('application', 'links');
  }
}
