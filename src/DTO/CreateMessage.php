<?php

namespace App\DTO;

readonly final class CreateMessage
{
    public function __construct(
        public string $content,
        public int $conversationId,
    ) {}
}
