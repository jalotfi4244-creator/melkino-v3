<?php
$from = (string)($from ?? 'profile.php');
echo \Melkino\UI\EmptyStates::render('user', 'ابتدا وارد شوید', 'برای مشاهده این بخش باید وارد حساب کاربری‌تان شوید.', 'login.php?redirect=' . rawurlencode($from), 'ورود به ملکینو');
