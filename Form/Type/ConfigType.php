<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticC15tBundle\Service\IntegrationRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * getBlockPrefix() MUST match EventListener/ConfigSubscriber.php's own
 * 'formAlias' exactly ('c15tconfig') -- confirmed against MessengerBundle's
 * own ConfigType/ConfigSubscriber pair.
 *
 * Per-integration fields (an enable toggle + its own params) are
 * GENERATED from Service/IntegrationRegistry.php's own getPackaged()
 * list, not hand-written per integration -- adding a new packaged
 * integration to that registry automatically grows this form with a
 * matching toggle + fields, no ConfigType.php change needed. Field names
 * are the integration's registry key with hyphens converted to
 * underscores (Symfony form field naming convention), e.g. 'google-tag'
 * -> google_tag_enabled / google_tag_id.
 *
 * Translation: general fields use real mautic.c15t.config.* keys
 * (Translations/en_US/messages.ini). Per-integration ENABLED toggles and
 * PARAM fields each share ONE parameterized key (%name%/%integration%/
 * %param% substitution) rather than one key per integration -- avoids a
 * 9-integration translation-key explosion for label shapes that are
 * otherwise identical. The %name%/%integration%/%param% VALUES
 * themselves come from IntegrationRegistry::getPackaged()'s own 'label'
 * entries, which are ALSO translation keys (mautic.c15t.integration.*) --
 * translated here via $this->translator->trans() before being
 * substituted in, so the final rendered text is fully translated end to
 * end, not just the compound-key wrapper.
 *
 * Requires this class to actually be autowired by Symfony's form factory
 * (constructor now has real dependencies, unlike its earlier no-args
 * version) -- explicitly registered as a service in Config/config.php
 * rather than left to auto-discovery, since the stakes of getting that
 * wrong are higher now (a broken constructor fails the whole
 * Configuration screen, not just one missing field). UNVERIFIED against
 * a live install -- see this repo's README "Local validation caveat".
 */
class ConfigType extends AbstractType
{
    public function __construct(
        private IntegrationRegistry $registry,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('domains', TextareaType::class, [
            'label'      => 'mautic.c15t.config.domains',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'rows'    => 4,
                'tooltip' => $this->translator->trans('mautic.c15t.config.domains.tooltip'),
            ],
        ]);

        $builder->add('backend_url', TextType::class, [
            'label'      => 'mautic.c15t.config.backend_url',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => $this->translator->trans('mautic.c15t.config.backend_url.tooltip'),
            ],
        ]);

        $builder->add('categories', ChoiceType::class, [
            'label'      => 'mautic.c15t.config.categories',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'multiple'   => true,
            'expanded'   => false,
            'choices'    => [
                'mautic.c15t.category.necessary'     => 'necessary',
                'mautic.c15t.category.functionality' => 'functionality',
                'mautic.c15t.category.experience'    => 'experience',
                'mautic.c15t.category.measurement'   => 'measurement',
                'mautic.c15t.category.marketing'     => 'marketing',
            ],
            'attr' => [
                'class'   => 'form-control',
                'tooltip' => $this->translator->trans('mautic.c15t.config.categories.tooltip'),
            ],
        ]);

        $builder->add('disable_default_css', YesNoButtonGroupType::class, [
            'label' => 'mautic.c15t.config.disable_default_css',
            'attr'  => [
                'tooltip' => $this->translator->trans('mautic.c15t.config.disable_default_css.tooltip'),
            ],
        ]);

        foreach ($this->registry->getPackaged() as $key => $integration) {
            $prefix          = str_replace('-', '_', $key);
            $integrationName = $this->translator->trans($integration['label']);

            $builder->add($prefix.'_enabled', YesNoButtonGroupType::class, [
                'label' => $this->translator->trans('mautic.c15t.config.integration_enabled', ['%name%' => $integrationName]),
                'attr'  => [
                    'tooltip' => $this->translator->trans('mautic.c15t.config.integration_enabled.tooltip', ['%name%' => $integrationName]),
                ],
            ]);

            foreach ($integration['params'] as $paramKey => $paramSpec) {
                $paramName = $this->translator->trans($paramSpec['label']);

                $builder->add($prefix.'_'.$paramKey, TextType::class, [
                    'label'      => $this->translator->trans('mautic.c15t.config.integration_param', [
                        '%integration%' => $integrationName,
                        '%param%'       => $paramName,
                    ]),
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.c15t.config.integration_param.tooltip', [
                            '%integration%' => $integrationName,
                            '%param%'       => $paramName,
                        ]),
                    ],
                ]);
            }
        }

        $builder->add('advanced_scripts_json', TextareaType::class, [
            'label'      => 'mautic.c15t.config.advanced_scripts_json',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'rows'    => 10,
                'tooltip' => $this->translator->trans('mautic.c15t.config.advanced_scripts_json.tooltip'),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'c15tconfig';
    }
}
