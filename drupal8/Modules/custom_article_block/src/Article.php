<?php

namespace Drupal\custom_article_block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\custom_article_block\Utility\ArticleUtility;

/**
 * Class article to extend and provide the features of this content type.
 */
class Article {

  use LoggerChannelTrait;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var int articleId.
   */
  protected $articleId;

  /**
   * @var int articleCategory.
   */
  protected $articleCategory;

  /**
   * Pass the dependency to the object constructor.
   */
  public function __construct(
    Connection $connection,
    AccountInterface $account) {
    $this->account = $account;
    $this->connection = $connection;
  }

  /**
   * Set the article id.
   *
   * @param int $article_id
   *   Article id.
   */
  public function setArticleId($article_id) {
    $this->articleId = $article_id;
    return $this;
  }

  /**
   * Get the Article id.
   *
   * @return int
   *   Article ID.
   */
  public function getArticleId() {
    return $this->articleId;
  }

  /**
   * Set the article category.
   *
   * @param int $article_category
   *   Article category.
   */
  public function setArticleCategory($article_category) {
    $this->articleCategory = $article_category;
    return $this;
  }

  /**
   * Get the article category.
   *
   * @return int
   *   Article category.
   */
  public function getArticleCategory() {
    return $this->articleCategory;
  }

  /**
   * Function to get article list.
   *
   * @return array
   *   Article data.
   */
  public function getArticleList() {
    try {
      $query = $this->connection->select('node_field_data', 'nfd');
      $query->fields('nfd', ['title', 'uid']);
      $query->addField('nb', 'body_value', 'desc');
      $query->addField('ttfd', 'name', 'category');
      $query->addField('ttfd', 'tid');
      $query->addField('ufd', 'name', 'author');
      $query->addJoin('left', 'node__body', 'nb',
      'nb.entity_id = nfd.nid');
      $query->addJoin('left', 'node__field_category', 'nfc',
      'nfc.entity_id = nfd.nid');
      $query->addJoin('left', 'taxonomy_term_field_data', 'ttfd',
      'ttfd.tid = nfc.field_category_target_id');
      $query->addJoin('left', 'users_field_data', 'ufd',
      'ufd.uid = nfd.uid');
      $query->condition('nfd.nid', $this->articleId, '!=');
      $query->condition('nfd.status', 1, '=');
      $query->condition('nfd.type', 'article', '=');
      $query->orderBy('nfd.created', 'DESC');
      // $query->groupBy('ttfd.name');
      $query->range(0, 5);
      $result = $query->execute()->fetchAll();
      $output = $this->prepareArticleBlock($result);
      if (!empty($output)) {

        return $output;
      }

    }
    catch (\Exception $e) {
      $logger = $this->getLogger('custom-article-block');
      $logger->error($e->getMessage());
    }
  }

  /**
   * Prepare article block.
   * 
   * @param array $data
   *    Article data
   * 
   * @return array
   *   Article block data.
   */
  public function prepareArticleBlock($data) {
    $uid = (int) $this->account->id();
    $category = $this->articleCategory;
    $output = [];
    foreach($data as $key => $value) {
      if ($category == $value->tid && $uid == $value->uid) {
        $output[$value->category]['sameCatSameAauthor'][] =  $value->title . "\n" .
        strip_tags($value->desc) . "($value->author)";
      }
      if ($category == $value->tid && $uid != $value->uid) {
        $output[$value->category]['sameCatDiffAuthor'][] = $value->title . "\n".
        strip_tags($value->desc) . "($value->author)";
      }
      if ($category != $value->tid && $uid == $value->uid) {
        $output[$value->category]['diffCatSameAuthor'][] =  $value->title . "\n".
        strip_tags($value->desc) . "($value->author)"; 
      }
      if ($category != $value->tid && $uid != $value->uid) {
        $output[$value->category]['diffCatDiffAuthor'][] =  $value->title . "\n" .
        strip_tags($value->desc) . "($value->author)";
      }
    }

    return $output;
  }
    
}

