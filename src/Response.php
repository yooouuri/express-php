<?php

namespace ExpressPHP;

final class Response
{
    private \React\Http\Message\Response $response;

    public function getResponse(): \React\Http\Message\Response
    {
        return $this->response;
    }

    public function __construct()
    {
        $this->response = new \React\Http\Message\Response();
    }

    public function body(string $body): self
    {
        $self = clone $this;
        $stream = $this->response->getBody();
        $stream->write($body);

        $this->response = $this->response->withBody($stream);

        return $self;
    }

    public function status($status = 200): self
    {
        $this->response->withStatus($status);

        return clone $this;
    }

    public function json(mixed $content): self
    {
        $self = clone $this;

        $encoded = json_encode($content);

        return $self->body($encoded);
    }
}
