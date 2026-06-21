<?php

namespace App\Support;

final class ManagedQueue
{
    /** Must match the managed queue name in Laravel Cloud. */
    public const NAME = 'imports';
}