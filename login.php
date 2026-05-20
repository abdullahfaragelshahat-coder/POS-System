<?php
session_start();
include 'connect.php'; // الربط الموحد على store_db

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "<div class='error'>يرجى ملء جميع الحقول المطلوبة!</div>";
    } else {
        // البحث عن الحساب في قاعدة البيانات
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // التحقق من الباسورد (سواء كان مشفر بـ password_hash أو عادي للتجربة)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                
                // حفظ البيانات الحيوية في الجلسة لتشغيل نظام الصلاحيات (RBAC)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; 

                // التوجيه المباشر لشاشة البيع والفواتير الصادرة
                header("Location: sales.php");
                exit();
            } else {
                $message = "<div class='error'>عذراً، كلمة المرور غير صحيحة! ❌</div>";
            }
        } else {
            $message = "<div class='error'>هذا الحساب غير مسجل بالمنظومة! 🔍</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول للمنظومة</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
        body { background:#e9ecef; display:flex; justify-content:center; align-items:center; height:100vh; }
        .container { width:400px; background:#fff; padding:35px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #b8952e; }
        h2 { text-align: center; margin-bottom: 25px; color: #333; font-size: 24px; }
        form { display: flex; flex-direction: column; gap: 15px; }
        input, button { padding:12px 15px; border-radius:6px; border:1px solid #ccc; font-size: 15px; outline: none; }
        input:focus { border-color: #b8952e; box-shadow: 0 0 5px rgba(184, 149, 46, 0.3); }
        button { background:#b8952e; color:#fff; font-weight:bold; cursor:pointer; border:none; transition: 0.2s; }
        button:hover { background: #967923; }
        .error { background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; text-align:center; font-size:14px; font-weight: bold; }
        .footer-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .footer-link a { color: #b8952e; text-decoration: none; font-weight: bold; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>تسجيل الدخول للمنظومة 🔓</h2>
    <?php echo $message; ?>
    
    <form method="POST">
        <input type="email" name="email" placeholder="البريد الإلكتروني للعمل" required>
        <input type="password" name="password" placeholder="كلمة المرور السرية" required>
        <button type="submit">دخول للمحل 🔑</button>
    </form>

    <div class="footer-link">
        موظف جديد؟ <a href="register.php">إنشاء حساب جديد من هنا</a>
    </div>
</div>

</body>
</html>