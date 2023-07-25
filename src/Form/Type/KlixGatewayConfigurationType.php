<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use WhiteDigital\SyliusKlixPlugin\Bridge\KlixBridgeInterface;

final class KlixGatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*->add(
                'environment',
                ChoiceType::class,
                [
                    'choices' => [
                        'whitedigital.klix_plugin.secure' => KlixBridgeInterface::SECURE_ENVIRONMENT,
                        'whitedigital.klix_plugin.sandbox' => KlixBridgeInterface::SANDBOX_ENVIRONMENT,
                    ],
                    'label' => 'whitedigital.klix_plugin.environment',
                ]
            )*/
            ->add(
                'api_key',
                TextType::class,
                [
                    'label' => 'whitedigital.sylius_klix_plugin.api_key',
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'whitedigital.sylius_klix_plugin.gateway_configuration.api_key.not_blank',
                                'groups' => ['sylius'],
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'brand_id',
                TextType::class,
                [
                    'label' => 'whitedigital.sylius_klix_plugin.brand_id',
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'whitedigital.sylius_klix_plugin.gateway_configuration.brand_id.not_blank',
                                'groups' => ['sylius'],
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'endpoint',
                TextType::class,
                [
                    'label' => 'whitedigital.sylius_klix_plugin.endpoint',
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'whitedigital.sylius_klix_plugin.gateway_configuration.endpoint.not_blank',
                                'groups' => ['sylius'],
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'target_url',
                TextType::class,
                [
                    'label' => 'whitedigital.sylius_klix_plugin.target_url',
                ]
            )
            ->add(
                'notify_url',
                TextType::class,
                [
                    'label' => 'whitedigital.sylius_klix_plugin.notify_url',
                ]
            );
    }
}
