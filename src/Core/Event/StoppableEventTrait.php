<?php

namespace App\Core\Event;

trait StoppableEventTrait
{
    private bool $propagationStopped = false;
    private bool $rejected = false;
    private ?string $rejectionReason = null;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function setRejected(bool $rejected, ?string $reason = null): void
    {
        $this->rejected = $rejected;
        $this->rejectionReason = $reason;
        
        if ($rejected) {
            $this->stopPropagation();
        }
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
}
