<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\C15tBundle\Service\IntegrationRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

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

        $builder->add('test_domains', TextareaType::class, [
            'label'      => 'mautic.c15t.config.test_domains',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'rows'    => 4,
                'tooltip' => $this->translator->trans('mautic.c15t.config.test_domains.tooltip'),
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

        $builder->add('reload_on_restrict', YesNoButtonGroupType::class, [
            'label' => 'mautic.c15t.config.reload_on_restrict',
            'attr'  => [
                'tooltip' => $this->translator->trans('mautic.c15t.config.reload_on_restrict.tooltip'),
            ],
        ]);

        $builder->add('enable_focus_trap', YesNoButtonGroupType::class, [
            'label' => 'mautic.c15t.config.enable_focus_trap',
            'attr'  => [
                'tooltip' => $this->translator->trans('mautic.c15t.config.enable_focus_trap.tooltip'),
            ],
        ]);

        $builder->add('banner_text', TextareaType::class, [
            'label'      => 'mautic.c15t.config.banner_text',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'rows'        => 3,
                'placeholder' => 'We use cookies to run this site and, if you agree, to measure usage and show relevant marketing.',
                'tooltip'     => $this->translator->trans('mautic.c15t.config.banner_text.tooltip'),
            ],
        ]);

        $builder->add('modal_text', TextareaType::class, [
            'label'      => 'mautic.c15t.config.modal_text',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'rows'        => 3,
                'placeholder' => 'Choose which categories of cookies you allow. Necessary cookies are always on.',
                'tooltip'     => $this->translator->trans('mautic.c15t.config.modal_text.tooltip'),
            ],
        ]);

        // Policy packs / consent model are NOT configured here -- they live
        // entirely on consent-backend's own c15tInstance() call (a
        // separate repo), not this plugin. An earlier version of this
        // form had 'consent_mode'/'policy_packs' fields that fed a
        // client-side option (getOrCreateConsentRuntime()'s policyPacks)
        // which turned out not to exist at all -- hosted-mode clients
        // defer entirely to the backend's own /init response for
        // jurisdiction/policy resolution. Confirmed against c15t's own
        // self-host policy-packs guide, 2026-08-18. See
        // consent-backend/src/c15t.js's own header comment for where this
        // actually lives now.
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
                if (isset($paramSpec['source'])) {
                    // Auto-filled from Mautic's own core config (see
                    // Service/IntegrationRegistry.php's docblock) --
                    // nothing for the admin to fill in here.
                    continue;
                }

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

    /**
     * Hands the Twig form theme an explicit, unambiguous grouping of
     * which fields belong to which packaged integration -- deliberately
     * NOT left for the template to work out itself via field-name string
     * matching (e.g. "starts with '{prefix}_'"), because registry keys
     * aren't prefix-safe against each other: 'google-tag' is a literal
     * string prefix of 'google-tag-manager', so a naive
     * `paramName starts with 'google_tag_'` check in Twig also matched
     * 'google_tag_manager_enabled' and 'google_tag_manager_id', which
     * both got rendered a second time under the wrong panel and blew up
     * with Symfony's "Field ... has already been rendered" error on
     * first live deploy.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $groups = [];
        foreach ($this->registry->getPackaged() as $key => $integration) {
            $prefix = str_replace('-', '_', $key);
            $groups[] = [
                'enabledField' => $prefix.'_enabled',
                'paramFields'  => array_values(array_map(
                    static fn (string $paramKey): string => $prefix.'_'.$paramKey,
                    array_keys(array_filter(
                        $integration['params'],
                        static fn (array $paramSpec): bool => !isset($paramSpec['source'])
                    ))
                )),
            ];
        }

        $view->vars['c15tIntegrationGroups'] = $groups;
    }

    public function getBlockPrefix(): string
    {
        return 'c15tconfig';
    }
}
