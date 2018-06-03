<?php

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use cweagans\Composer\Patch;

class PatchTest extends Unit
{

    public function testSerializeDeserialize()
    {
        $patch = new Patch();
        $patch->package = 'drupal/drupal';
        $patch->url = 'https://google.com';
        $patch->description = "Test description";
        $patch->depth = 0;
        $patch->sha1 = sha1('asdf');

        $json = json_encode($patch);

        $new_patch = Patch::fromJson($json);

        $this->assertEquals($patch, $new_patch);
    }
}
