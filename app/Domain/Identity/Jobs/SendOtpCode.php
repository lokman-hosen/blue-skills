<?php

namespace App\Domain\Identity\Jobs;

use App\Domain\Identity\Contracts\OtpChannel;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The payload holds the plain code, so the job is encrypted on the queue.
 */
class SendOtpCode implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 15];

    public function __construct(
        public string $phone,
        public string $code,
        public string $locale,
    ) {}

    public function handle(OtpChannel $channel): void
    {
        $channel->send($this->phone, $this->code, $this->locale);
    }
}
