<?php

namespace Plugin\Sacombank\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\Sacombank\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class ConfigType extends AbstractType
{
    /** @var EccubeConfig */
    private $eccubeConfig;
    /**
     * ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('call_url', TextType::class, [
                'label' => trans('Sacombank.config.call_url.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                    new Url(),
                ],
            ])
            ->add('access_key', TextType::class, [
                'label' => trans('Sacombank.config.access_key.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                ],
            ])
            ->add('profile_id', TextType::class, [
                'label' => trans('Sacombank.config.profile_id.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                ],
            ])
            ->add('secret', TextareaType::class, [
                'label' => trans('Sacombank.config.secret.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_sltext_len')]),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
