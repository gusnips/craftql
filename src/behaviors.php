<?php

return [
    \craft\models\EntryType::class => [
        \markhuot\CraftQL\Behaviors\EntryType::class,
    ],
    \craft\elements\User::class => [
        \markhuot\CraftQL\Behaviors\User::class,
    ],
];