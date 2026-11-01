<?php
require_once __DIR__.'/../../controller/AuthController.php'; (new AuthController())->logout(); header('Location: ../login.php'); exit;
