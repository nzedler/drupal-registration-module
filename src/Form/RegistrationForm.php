<?php
/**
 * @file
 * Contains Drupal\drupal_registration_module\Form\RegistrationForm.
 */
namespace Drupal\drupal_registration_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class RegistrationForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_registration_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = array(
        '#type' => 'select',
        '#title' => ('Anrede auswählen:'),
        '#options' => array(
          'mr' => t('Herr'),
          'mrs' => t('Frau'),
          'divers' => t('Divers')
        ),
    );
    $form['firstname'] = array(
      '#type' => 'textfield',
      '#title' => t('Vorname:'),
      '#required' => TRUE
    );
    $form['lastname'] = array(
        '#type' => 'textfield',
        '#title' => t('Nachname:'),
        '#required' => TRUE
    );
    $form['email'] = array(
        '#type' => 'email',
        '#title' => t('Email:'),
        '#required' => TRUE
    );
    $form['password'] = array(
        '#type' => 'password',
        '#title' => t('Passwort:'),
        '#required' => TRUE
    );
    $form['password_repeat'] = array(
        '#type' => 'password',
        '#title' => t('Passwort wiederholen:'),
        '#required' => TRUE
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Registrieren'),
      '#button_type' => 'primary'
    );
    return $form;
  }
  
    /*
    * Validates the provided form data and prints out error messages if necessary.
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!in_array($form_state->getValue('title'), array("mr", "mrs", "divers"))) {
        $form_state->setErrorByName('title', $this->t('Anrede muss ausgewählt werden'));
    }
    if (empty($form_state->getValue('firstname'))) {
        $form_state->setErrorByName('firstname', $this->t('Vorname ist leer'));
    }
    if(!ctype_alpha($form_state->getValue('firstname'))) {
        $form_state->setErrorByName('firstname', $this->t('Vorname darf nur aus Buchstaben und - bestehen'));
    }
    if (empty($form_state->getValue('lastname'))) {
        $form_state->setErrorByName('lastname', $this->t('Nachname ist leer'));
    }
    if(!ctype_alpha($form_state->getValue('lastname'))) {
        $form_state->setErrorByName('lastname', $this->t('Nachname darf nur aus Buchstaben und - bestehen'));
    }
    if (!filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('email', $this->t('Email ist ungültig'));
    }
    // Validate password
    if (strlen($form_state->getValue('password')) <= 12) {
        $form_state->setErrorByName('password', $this->t('Passwort muss mindestens 12 Zeichen lang sein'));
    }
    if (strlen($form_state->getValue('password')) > 128) {
        $form_state->setErrorByName('password', $this->t('Passwort darf maximal 128 Zeichen lang sein'));
    }
    }
    if ($form_state->getValue('password') !== $form_state->getValue('password_repeat')) {
        $form_state->setErrorByName('password_repeat', $this->t('Passwörter stimmen nicht überein'));
    }
  }

    /*
    *  Simply sends the form data to the api_endpoint_url without any checks
    *
    *  @param   string  $api_endpoint_url   The url to the api endpoint
    *  @param   string  $title              The title of the user
    *  @param   string  $firstname          The firstname of the user
    *  @param   string  $lastname           The lastname of the user
    *  @param   string  $email              The email of the user
    *  @param   string  $password           The password of the user
    *  @return  number                      The status code
    */
    public static function send_formdata($api_endpoint_url, $title, $firstname, $lastname, $email, $password) {
        $data = array(
            'title' => $title, 
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $password
        );
        $args = array(
            'http' => array(
                'ignore_errors' => true,
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($args);
        $result = file_get_contents($api_endpoint_url, false, $context);
        if(is_array($http_response_header)) {
            $parts=explode(' ',$http_response_header[0]);
            if(count($parts)>1) //HTTP/1.0 <code> <text>
                return intval($parts[1]); //Get code
        }
        return 500;
    }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_endpoint_url = "https://api.e-government-portal.de/";
    //$api_endpoint_url = "http://ptsv2.com/t/aehcd-1657812044/post";

    $status = self::send_formdata(
        $api_endpoint_url, 
        $form_state->getValue('title'),
        $form_state->getValue('firstname'),
        $form_state->getValue('lastname'),
        $form_state->getValue('email'),
        $form_state->getValue('password')
    );
    if ($status === 200 or $status === 201) {
        \Drupal::messenger()->addMessage(t("Erfolgreich registriert."));
    } else {
        \Drupal::messenger()->addMessage(t("Sorry, etwas ist schiefgelaufen. Bitte später nochmal versuchen."));
    }
  }

}