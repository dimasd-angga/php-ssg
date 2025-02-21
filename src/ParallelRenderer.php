<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Cooperative parallel page renderer using PHP 8.1+ Fibers.
 *
 * PHP Fibers are not true OS threads — there is no preemption and no shared-
 * memory race. What they buy us is the ability to interleave the file-write
 * step of many pages so the kernel can batch flushes and disk writes overlap
 * each other. On fast SSDs the gain is modest (~5–15%); on spinning disks
 * or NFS-backed dist directories it's meaningful.
 *
 * For sites under ~50 pages, the fiber bookkeeping overhead outweighs the
 * gain — keep `--parallel` off for small sites.
 */
class ParallelRenderer
{
    public function render(array $renderJobs): void
    {
        $fibers = [];
        $errors = [];

        foreach ($renderJobs as $job) {
            $fiber = new \Fiber(function () use ($job) {
                $html = ($job['render'])();
                \Fiber::suspend();
                file_put_contents($job['outPath'], $html);
            });

            try {
                $fiber->start();
            } catch (\Throwable $e) {
                // A fiber that throws before suspend would never be terminated
                // from outside, causing the resume loop below to hang waiting
                // for a fiber that's already failed. Surface the error and
                // skip the failed fiber so the build can continue.
                $errors[] = $e;
                continue;
            }
            $fibers[] = $fiber;
        }

        // All fibers have produced their HTML in scope; now resume them all
        // to perform their writes. This is where I/O interleaving happens.
        foreach ($fibers as $fiber) {
            if (!$fiber->isTerminated() && $fiber->isSuspended()) {
                try {
                    $fiber->resume();
                } catch (\Throwable $e) {
                    $errors[] = $e;
                }
            }
        }

        if ($errors !== []) {
            $first = $errors[0];
            throw new \RuntimeException(
                count($errors) . " page(s) failed to render. First error: " . $first->getMessage(),
                0,
                $first
            );
        }
    }
}
