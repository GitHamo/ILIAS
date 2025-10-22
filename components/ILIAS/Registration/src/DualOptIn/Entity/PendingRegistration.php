<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Registration\DualOptIn\Entity;

use DateTimeImmutable;
use ILIAS\Data\ObjectId;
use ILIAS\Registration\DualOptIn\ValueObjects\PendingRegistrationHash;
use ILIAS\Registration\DualOptIn\ValueObjects\PendingRegistrationId;

final readonly class PendingRegistration
{
    public function __construct(
        private PendingRegistrationId $id,
        private ObjectId $usr_id,
        private PendingRegistrationHash $hash,
        private DateTimeImmutable $created_at
    ) {
    }

    public function id(): PendingRegistrationId
    {
        return $this->id;
    }

    public function userId(): ObjectId
    {
        return $this->usr_id;
    }

    public function hash(): PendingRegistrationHash
    {
        return $this->hash;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
