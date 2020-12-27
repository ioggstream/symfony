<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\RateLimiter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Limit;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\NoLimiter;

/**
 * An implementation of RequestRateLimiterInterface that
 * fits most use-cases.
 * See https://datatracker.ietf.org/doc/draft-ietf-httpapi-ratelimit-headers/
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 *
 * @experimental in Symfony 5.2
 */
abstract class AbstractRequestRateLimiter implements RequestRateLimiterInterface
{
    public function consume(Request $request): Limit
    {
        $limiters = $this->getLimiters($request);
        if (0 === \count($limiters)) {
            $limiters = [new NoLimiter()];
        }

        $minimalLimit = null;
        foreach ($limiters as $limiter) {
            $limit = $limiter->consume(1);

            if (null === $minimalLimit || $limit->getRemainingTokens() < $minimalLimit->getRemainingTokens()) {
                $minimalLimit = $limit;
            }
        }

        return $minimalLimit;
    }

    public function reset(): void
    {
        foreach ($this->getLimiters($request) as $limiter) {
            $limiter->reset();
        }
    }

    /**
     * @return LimiterInterface[] a set of limiters using keys extracted from the request
     */
    abstract protected function getLimiters(Request $request): array;
}
