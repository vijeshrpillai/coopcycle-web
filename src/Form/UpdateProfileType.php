<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Entity\LocalBusiness;
use AppBundle\Entity\Store;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Doctrine\Persistence\ManagerRegistry;

class UpdateProfileType extends AbstractType
{
    private $tokenStorage;
    private $translator;
    private $countryIso;

    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator, $countryIso)
    {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->countryIso = strtoupper($countryIso);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'profile.username',
                'required' => false,
            ])
            ->add('fullName', TextType::class, [
                'label' => 'profile.fullName',
                'required' => false,
                'property_path' => 'customer.fullName',
            ])
            ->add('telephone', PhoneNumberType::class, [
                'label' => 'profile.telephone',
                'format' => PhoneNumberFormat::NATIONAL,
                'default_region' => strtoupper($this->countryIso),
                'required' => false,
            ]);

        $isAdmin = false;
        if ($token = $this->tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                $isAdmin = $user->hasRole('ROLE_ADMIN');
            }
        }

        if ($isAdmin) {
            $builder
                ->add('quotesAllowed', CheckboxType::class, [
                'label' => 'form.user.quotes_allowed.label',
                'required' => false,
            ])->add('tags', TagsType::class, [
                'mapped' => false,
                'label' => 'adminDashboard.tags.title'
            ]);
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $isAdmin) {

                $form = $event->getForm();
                $user = $event->getData();
                $log = new Logger('PRE_SET_DATA');
                $log->pushHandler(new StreamHandler('php://stdout', Logger::WARNING)); // <<< uses a stream
                $log->warning('UpdateProfileType');
                $log->warning('PRE_SET_DATA');
                if ($isAdmin) {
                    $form
                        ->add('enabled', CheckboxType::class, [
                            'label' => 'user.edit.enabled.label',
                            'required' => false
                        ])
                        ->add('email', EmailType::class, [
                            'label' => 'profile.email'
                        ]);
                }

                if ($isAdmin && $options['with_roles'] && !empty($options['editable_roles'])) {
                    $data = array_filter($user->getRoles(), function ($role) use ($options) {
                        return in_array($role, $options['editable_roles']);
                    });
                    $choices = array_combine($options['editable_roles'], $options['editable_roles']);
                    $event->getForm()->add('roles', ChoiceType::class, [
                        // Use mapped = false for security reasons
                        'mapped' => false,
                        'choices' => $choices,
                        'choice_label' => function ($value, $key, $index) {
                            return $this->translator->trans("roles.{$key}.help");
                        },
                        'expanded' => true,
                        'multiple' => true,
                        'data' => $data
                    ]);
                }

                if ($user->getCustomer()->hasLoopEatCredentials()) {
                    $event->getForm()->add('loopeatDisconnect', SubmitType::class, [
                        'label' => 'profile.loopeat.disconnect',
                    ]);
                }

                if ($user->getCustomer()->hasDabbaCredentials()) {
                    $event->getForm()->add('dabbaDisconnect', SubmitType::class, [
                        'label' => 'profile.dabba.disconnect',
                    ]);
                }

                if ($user->hasRole('ROLE_RESTAURANT') && $options['with_restaurants']) {
                    $event->getForm()->add('restaurants', CollectionType::class, array(
                        'entry_type' => EntityType::class,
                        'entry_options' => array(
                            'label' => 'Restaurants',
                            'class' => LocalBusiness::class,
                            'choice_label' => 'name',
                        ),
                        'allow_add' => true,
                        'allow_delete' => true,
                        'label' => 'profile.managedRestaurants'
                    ));
                }

                if ($user->hasRole('ROLE_STORE') && $options['with_stores']) {
                    $event->getForm()->add('stores', CollectionType::class, array(
                        'entry_type' => EntityType::class,
                        'entry_options' => array(
                            'label' => 'Stores',
                            'class' => Store::class,
                            'choice_label' => 'name',
                        ),
                        'allow_add' => true,
                        'allow_delete' => true,
                        'label' => 'profile.managedStores'
                    ));
                }
            }
        );
/*
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $user = $event->getData();

            if (null === $user) {
                return;
            }
            $log = new Logger('POST_SET_DATA');
            $log->pushHandler(new StreamHandler('php://stdout', Logger::WARNING)); // <<< uses a stream
            $log->warning('UpdateProfileType');
            $log->warning('POST_SET_DATA');
            //$log->warning($user->getTags()->count());
            $form->get('tags')->setData(implode(' ', $user->getTags()));
            $log->warning(print_r($user->getCustomer()->getTags(), true));
            $form->get('tags')->setData($user->getTags());
        });

        $builder->get('tags')->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $user = $event->getForm()->getParent()->getData();
            if (null === $user) {
                return;
            }
            $log = new Logger('PRE_SUBMIT');
            $log->pushHandler(new StreamHandler('php://stdout', Logger::WARNING)); // <<< uses a stream
            $log->warning('UpdateProfileType');
            $log->warning('PRE_SUBMIT');
            //$log->warning(var_dump($user));
            //$log->warning(var_dump($event));
            $tags = $event->getData();
            //$tags = explode(' ', $tags);

            $user->setTags($tags);
            //$user->getUser()->setTags($tags);
        });

        */
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
           'data_class' => User::class,
           'with_restaurants' => false,
           'with_stores' => false,
           'with_roles' => false,
           'editable_roles' => []
        ));
    }
}
