<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

/**
 * An IMAP message (e-mail).
 */
interface MessageInterface extends Message\BasicMessageInterface
{
    /**
     * Get raw part content.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Get message recent flag value (from headers).
     *
     * @return string
     */
    public function isRecent(): string;

    /**
     * Get message unseen flag value (from headers).
     *
     * @return bool
     */
    public function isUnseen(): bool;

    /**
     * Get message flagged flag value (from headers).
     *
     * @return bool
     */
    public function isFlagged(): bool;

    /**
     * Get message answered flag value (from headers).
     *
     * @return bool
     */
    public function isAnswered(): bool;

    /**
     * Get message deleted flag value (from headers).
     *
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * Get message draft flag value (from headers).
     *
     * @return bool
     */
    public function isDraft(): bool;

    /**
     * Has the message been marked as read?
     *
     * @return bool
     */
    public function isSeen(): bool;

    /**
     * Mark message as seen.
     *
     * @return bool
     */
    public function maskAsSeen(): bool;

    /**
     * Move message to another mailbox.
     *
     * @param Mailbox $mailbox
     */
    public function copy(MailboxInterface $mailbox);

    /**
     * Move message to another mailbox.
     *
     * @param Mailbox $mailbox
     */
    public function move(MailboxInterface $mailbox);

    /**
     * Delete message.
     */
    public function delete();

    /**
     * Set Flag Message.
     *
     * @param $flag \Seen, \Answered, \Flagged, \Deleted, and \Draft
     *
     * @return bool
     */
    public function setFlag(string $flag): bool;

    /**
     * Clear Flag Message.
     *
     * @param $flag \Seen, \Answered, \Flagged, \Deleted, and \Draft
     *
     * @return bool
     */
    public function clearFlag(string $flag): bool;
}