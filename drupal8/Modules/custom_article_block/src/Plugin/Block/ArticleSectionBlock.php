<?php

namespace Drupal\custom_article_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\custom_article_block\Utility\ArticleUtility;
use Drupal\custom_article_block\Article;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'Article Section' Block.
 *
 * @Block(
 *   id = "article_section_block",
 *   admin_label = @Translation("Article Section Block"),
 *   category = @Translation("Article Section Block"),
 * )
 */
class ArticleSectionBlock extends BlockBase implements ContainerFactoryPluginInterface {
   

  /**
   * @var mixed $currentPath
   */
  protected $currentPath;

  /**
   * Object EntityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * 
   */
  protected $article;

  /**
   * ProductBannerBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param mixed $request_stack
   *   The plugin request stack service.
   * @param mixed $entity_type_manager
   *   The EntityTypeManagerInterface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
  RequestStack $request_stack,
  EntityTypeManagerInterface $entity_type_manager,
  Article $article) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->article = $article;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('custom_article_block.article')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = '';
    $current_path = $this->currentPath->getCurrentRequest();
    // If path is created by alias then if could follow this
    // Else we can get arg by exploding the current path.
    $id = ArticleUtility::getIdByPath($current_path);
    $node = $this->entityTypeManager->getStorage('node')->load($id);
    $node_data = $node->toArray();
    $article_cat_id = array_map('intval',
        array_column($node_data['field_category'], 'target_id'));
    
    //Set article Id to fetch the list of articles other then current article.
    $articles = $this->article->setArticleId($id)->setArticleCategory($article_cat_id[0])->getArticleList();
    return [
      '#theme' => 'article_section_block',
      '#articleData' => $articles,
      // '#cache' => [
      //   'tags' => ['article-section:' . $id],
      // ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

