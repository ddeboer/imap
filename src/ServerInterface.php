<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

/**
 * An IMAP server.
 */
interface ServerInterface
{
    /**
     * Authenticate connection.
     *
     * @param string $username Username
     * @param string $password Password
     */
    public function authenticate(string $username, string $password): ConnectionInterface;

    /**
     * Access a (shared) mailbox (for office365) directly while using the credentials of another user (using the authenticate method).
     *
     * @param string $mailbox
     * @return static
     */
    public function forMailbox(string $mailbox): static;
}
