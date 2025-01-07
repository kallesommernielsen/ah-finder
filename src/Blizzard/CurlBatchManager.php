<?php

namespace Blizzard;

class CurlBatchManager
{
    protected array $batch = [];

    public function __construct(
        protected readonly \Closure $startCallback,
        protected readonly \Closure $endCallback,
        protected readonly int $maxActive = 200,
    ) {
    }

    public function add(
        \CurlHandle $curlHandle,
        mixed $callbackData = null,
    ): void {
        $this->batch[\spl_object_hash($curlHandle)] = [
            CurlBatchStatus::NOT_STARTED,
            $curlHandle,
            $callbackData,
        ];
    }

    public function run(): void
    {
        $mh = \curl_multi_init();

        for ($i = 0; $i < $this->maxActive; ++$i) {
            $this->startNextAvailableHandle($mh);
        }

        do {
            $status = \curl_multi_exec($mh, $active);

            if ($active) {
                \curl_multi_select($mh);

                $info = \curl_multi_info_read($mh);

                if ($info !== false && $info['result'] === CURLE_OK) {
                    $this->startNextAvailableHandle($mh);
                }
            }
        } while ($active && $status === \CURLM_OK);

        $this->finishDangling();
    }

    protected function startNextAvailableHandle(\CurlMultiHandle $mh): void
    {
        foreach ($this->batch as $hash => $batch) {
            if ($batch[0] === CurlBatchStatus::NOT_STARTED) {
                \curl_multi_add_handle($mh, $batch[1]);
                ($this->startCallback)($batch[2]);

                $this->batch[$hash][0] = CurlBatchStatus::STARTED;

                return;
            }
        }
    }

    protected function decodeResponse(\CurlHandle $curlHandle): string
    {
        $returnValue = \curl_multi_getcontent($curlHandle);

        if (!\is_string($returnValue) || !\json_validate($returnValue)) {
            throw new \RuntimeException('Unable to complete request');
        }
        return $returnValue;
    }

    protected function completeHandle(\CurlHandle $curlHandle): void
    {
        $hash = \spl_object_hash($curlHandle);
        $this->batch[$hash][0] = CurlBatchStatus::COMPLETE;

        ($this->endCallback)($this->batch[$hash][2], $this->decodeResponse($curlHandle));
    }

    protected function finishDangling(): void
    {
        foreach ($this->batch as $hash => $batch) {
            if ($batch[0] !== CurlBatchStatus::STARTED) {
                continue;
            }

            $this->completeHandle($batch[1]);
        }
    }
}
