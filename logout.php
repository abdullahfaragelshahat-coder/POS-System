<?php
session_start();

// تفريغ كل بيانات الجلسة (السيسشن) بالكامل
session_unset();

// تدمير الجلسة نهائياً من السيرفر
session_destroy();

// تحويل المستخدم فوراً لصفحة تسجيل الدخول الجديدة
header("Location: login.php");
exit();
?>