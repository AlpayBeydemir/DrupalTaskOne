<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Exception;

class EventRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
    ];

    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#attributes' => [
        'placeholder' => $this->t('xxx-xxx-xxxx'),
        'maxlength' => 11,
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
    ];

    $form['birth_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Birth Date'),
    ];

    $form['newsletter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Subscribe to our newsletter'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if (empty($form_state->getValue('first_name'))) {
      $form_state->setErrorByName('first_name', $this->t('The first name is required.'));
    }

    if (empty($form_state->getValue('last_name'))) {
      $form_state->setErrorByName('last_name', $this->t('The last name is required.'));
    }

    if (!is_numeric($form_state->getValue('phone_number')) || strlen($form_state->getValue('phone_number')) != 11) {
      $form_state->setErrorByName('phone_number', $this->t('The phone number must be 11 digits.'));
    }

    if (!filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('The email address is not valid.'));
    }

    if (empty($form_state->getValue('birth_date'))) {
      $form_state->setErrorByName('birth_date', $this->t('The birth date is required.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    try {
      $connection = Database::getConnection();
      $fields = $form_state->getValues();
      $connection->insert('event_registration')
        ->fields($fields)
        ->execute();

      $this->messenger()->addStatus($this->t('Thank you for registering! Your registration has been submitted.'));
      $form_state->setRedirect('event_registration');

    } catch (Exception $e) {
      \Drupal::logger('event_registration')->error($e->getMessage());
    }
  }
}
