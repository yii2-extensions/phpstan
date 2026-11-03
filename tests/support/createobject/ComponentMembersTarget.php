<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target with native properties, setters and a getter, and a setter that attaches {@see ComponentBehavior}.
 */
final class ComponentMembersTarget extends Component
{
    public int $count = 0;
    public string $name = '';
    public static int $shared = 0;
    protected int $guarded = 0;
    protected string $internal = '';
    private string $secret = '';

    public function getTitle(): string
    {
        $this->setHidden($this->internal);

        return $this->secret;
    }

    public function setAttach(bool $attach): void
    {
        if ($attach) {
            $this->attachBehavior('attached', new ComponentBehavior());
        }
    }

    public function setGuarded(int $guarded): void
    {
        $this->guarded = $guarded;
    }

    public function setLimit(int $limit): void
    {
        $this->internal = (string) $limit;
    }

    private function setHidden(string $hidden): void
    {
        $this->secret = $hidden;
    }
}
