<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');
        if ($connection !== 'mysql' || ! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new \RuntimeException('Test dihentikan: wajib MySQL dan nama database berakhiran _test. Jangan memakai database operasional.');
        }

        return $app;
    }
}
