<?php

/**
 * User: fad
 * Date: 06.09.12
 * Time: 18:39
 */
class DefaultController extends Controller
{
    /**
     * Declares class-based actions.
     */
    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class'     => 'CCaptchaAction',
                'backColor' => 0xf5f5f5,
            ),
        );
    }

    /**
     * Displays the contact page
     */
    public function actionIndex()
    {
        $model = new ContactForm;
        if (isset($_POST['ContactForm'])) {
            $model->attributes = $_POST['ContactForm'];

            if ($model->validate()) {
                $message = new YiiMailMessage;
                $body = '';
                foreach ($model->attributes as $attribute => $value) {
                    if (in_array($attribute, array('verifyCode'))) {
                        continue;
                    }
                    if ($value) {
                        $body .= $model->getAttributeLabel($attribute) . ": " . $value . "\r\n\r\n";
                    }
                }
                $body .= "\r\nReferer: " . Yii::app()->request->getUrlReferrer();
                $body .= "\r\nIP: " . Yii::app()->request->userHostAddress;

                if (!$model->subject) {
                    $model->subject = Yii::t('contact', 'Письмо с сайта ' . Yii::app()->name);
                }

                $message->setBody($body, 'text/html', Yii::app()->charset);
                $message->subject = $model->subject;
                $message->addTo($this->admin->email);
                $message->setReplyTo($model->email);
                if (Yii::app()->getModule('contact')->smtpEnabled) {
                    $message->from = Yii::app()->getModule('contact')->smtpUserName;
                } else if(Yii::app()->getModule('contact')->setFrom) {
                    $message->from = $this->admin->email;
                }
                if (Yii::app()->mail->send($message)) {
                    Yii::app()->user->setFlash('success', Yii::t('contact', 'Спасибо за сообщение! Мы Вам обязательно ответим!'));
                } else {
                    Yii::app()->user->setFlash('error', Yii::t('contact', 'Произошла проблема с отправкой письма! Обратитесь к администратоу: ' . $this->admin->email));
                }
                #$this->refresh();
            }
        }
        $this->render('contact', array('model' => $model));
    }

    public function actionFull()
    {
        if (empty(Yii::app()->getModule('contact')->fullFormClass)) {
            throw new CHttpException(400, Yii::t('yii', 'Your request is invalid.'));
        }
        $modelName = Yii::app()->getModule('contact')->fullFormClass;
        $viewName  = strtolower($modelName[0]) . substr($modelName, 1);
        if (!empty($modelName) && class_exists($modelName)) {
            /** @var $model CActiveRecord */
            $model = new $modelName;
            if (isset($_POST[$modelName])) {
                $model->attributes = $_POST[$modelName];
                if ($model->validate()) {
                    $message = new YiiMailMessage;
                    $message->view = $viewName;
                    $message->setBody(array('model' => $model), 'text/html', Yii::app()->charset);
                    $message->subject = Yii::t('contact', 'Письмо с сайта {site}', array('{site}' => Yii::app()->name));
                    $message->addTo($this->admin->email);
                    $message->setReplyTo($model->email);

                    if (Yii::app()->getModule('contact')->smtpEnabled) {
                        $message->from = Yii::app()->getModule('contact')->smtpUserName;
                    } else if(Yii::app()->getModule('contact')->setFrom) {
                        $message->from = $this->admin->email;
                    }

                    if (Yii::app()->mail->send($message)) {
                        Yii::app()->user->setFlash('success', Yii::app()->getModule('contact')->fullFormMessage);
                    }
                }
            }
            $this->render($viewName, array('model' => $model));
        }
    }

    public function getSenderInfo()
    {
        return array(
            'referer' => Yii::app()->request->getUrlReferrer(),
            'ip'      => Yii::app()->request->userHostAddress
        );
    }
}
