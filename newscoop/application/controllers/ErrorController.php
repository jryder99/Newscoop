<?php

class ErrorController extends Zend_Controller_Action
{
    /**
     * Forward to legacy controller if controller/action not found
     */
    public function preDispatch()
    {
        $errors = $this->_getParam('error_handler');
        if (!$errors) {
            return;
        }

        $notFound = array(
            Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER,
            Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION,
        );

        if (in_array($errors->type, $notFound) && $this->_getParam('module') == 'admin') { // handle with old code
            $this->_forward('index', 'legacy', 'admin');
        }
    }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $request = $this->getRequest();

        if (!$errors) {
            $this->view->message = 'You have reached the error page';
            return;
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}
