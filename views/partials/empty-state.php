<?php
/** Vars: $icon,$title,$text,$action_url,$action_label */
echo \Melkino\UI\EmptyStates::render(
    (string)($icon ?? 'search'),
    (string)($title ?? 'موردی پیدا نشد'),
    (string)($text ?? ''),
    (string)($action_url ?? ''),
    (string)($action_label ?? '')
);
