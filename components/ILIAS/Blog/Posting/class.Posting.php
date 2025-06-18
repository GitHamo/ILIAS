<?php

declare(strict_types=1);

namespace ILIAS\Blog\Posting;

use ilDateTime;

class Posting
{
    public function __construct(
        protected int $id,
        protected int $blog_id,
        protected string $title,
        protected ilDateTime $created,
        protected int $author,
        protected bool $approved,
        protected ?ilDateTime $last_withdrawn
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBlogId(): int
    {
        return $this->blog_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreated(): ilDateTime
    {
        return $this->created;
    }

    public function getAuthor(): int
    {
        return $this->author;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function getLastWithdrawn(): ?ilDateTime
    {
        return $this->last_withdrawn;
    }
}
