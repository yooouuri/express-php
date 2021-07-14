<?php

namespace ExpressPHP;

final class Response
{
    private int $status;

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus($status = 200): self
    {
        $self = clone $this;
        $self->status = $status;

        return $self;
    }

    private string $body;

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $self = clone $this;
        $self->body = $body;

        return $self;
    }

    private array $headers;

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function addHeader(string $key, string $value): self {
        $self = clone $this;

        $self->headers[$key] = $value;

        return $self;
    }

    public function __construct(int $status = 200,
                                string $body = '',
                                array $headers = [ 'Content-Type' => 'text/html' ])
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
    }

    public function json(mixed $content): self
    {
        $self = clone $this;

        $encoded = json_encode($content);

        return $self
            ->setBody($encoded)
            ->addHeader('Content-Type', 'application/json');
    }
}
