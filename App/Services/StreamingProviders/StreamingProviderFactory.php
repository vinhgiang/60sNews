<?php

namespace App\Services\StreamingProviders;

use App\Services\StreamingProviders\Domain\Htv7;
use App\Services\StreamingProviders\Domain\Htv7Hd;
use App\Services\StreamingProviders\Domain\Htv7Sctv;
use App\Services\StreamingProviders\Domain\StreamingProvider;
use App\Services\StreamingProviders\Domain\Thvl1;
use Exception;

class StreamingProviderFactory
{
    /**
     * @param string $provider
     * @return StreamingProvider
     * @throws Exception
     */
    public static function build($provider)
    {
        switch ($provider) {
            case Htv7::class:
                return new Htv7();
            case Htv7Hd::class:
                return new Htv7Hd();
            case Htv7Sctv::class:
                return new Htv7Sctv();
            case Thvl1::class:
                return new Thvl1();
            default:
                throw new Exception("Invalid streaming provider: $provider");
        }
    }
}