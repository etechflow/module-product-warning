<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Api\Data;

interface WarningInterface
{
    public const WARNING_ID = 'warning_id';
    public const NAME       = 'name';
    public const MESSAGE    = 'message';
    public const COLOR      = 'color';
    public const IS_ACTIVE  = 'is_active';
    public const SORT_ORDER = 'sort_order';

    public function getWarningId();
    public function setWarningId($id);
    public function getName(): ?string;
    public function setName(string $name);
    public function getMessage(): ?string;
    public function setMessage(string $message);
    public function getColor(): ?string;
    public function setColor(string $color);
    public function getIsActive(): bool;
    public function setIsActive(bool $isActive);
    public function getSortOrder(): int;
    public function setSortOrder(int $sortOrder);

    /** @return int[] */
    public function getCategoryIds(): array;
    public function setCategoryIds(array $ids);
    /** @return int[] */
    public function getProductIds(): array;
    public function setProductIds(array $ids);
}
