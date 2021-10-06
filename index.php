<?php
    require_once './controllers/basecontroller.php';
    require_once './core/controllers.php';
    require_once './core/database.php';

    $controller = (isset($_GET['controller']) ? strtolower($_GET['controller']) : 'home');
    $action = (isset($_GET['action']) ? $_GET['action'] : 'index');

    if (array_key_exists($controller, CONTROLLERS))
    {
        $fileName = CONTROLLERS[$controller]['fileName'];
        $className = CONTROLLERS[$controller]['className'];
        $classAction = CONTROLLERS[$controller]['action'];

        require './controllers/'.$fileName;
        
        $objController = new $className;

        if (in_array($action, $classAction))
        {
            $objController->$action();
        }
        else
        {
            require_once './views/404.php';
        }
    }
    else
    {
        require_once './views/404.php';
    }
?>