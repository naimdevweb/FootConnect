<?php

namespace App\Services;

use App\Entity\Conversation;
use Symfony\Component\HttpFoundation\RequestStack;

class TopicService{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }


    public function getTopicUrl(Conversation $conversation): string
    {
        return "{$this->getServerUrl()}/conversation/{$conversation->getId()}";
    }

    private function getServerUrl(): string
    {
        $request = $this->requestStack->getMainRequest();

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $portUrl = $port ? ":$port" : '';

        return $scheme . '://' . $host . $portUrl;
    }

}