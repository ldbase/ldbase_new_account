<?php

namespace Drupal\ldbase_new_account\Service;

use Drupal\node\Entity\Node;

/* 
 * Helper functions for the ldbase_new_account module
 */

class LDbaseNewAccountService {
  
  public function retrieveContentByPersonId($person_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'dataset')
      ->condition('field_related_persons', $person_id);
   
    $content = $query->execute();
    return $content;    
  }  
    
  public function nodeFromItemId ($ItemId) {
    $ItemId_parsed = explode(':', $ItemId);
    $node = explode('/', $ItemId_parsed[1]);
    return Node::load($node[1]);
  }
  
  public function personSearch(array $submission_array) {   
    $primary_first_name = $submission_array['ldbase_primary_name'][0]['primary_first_name'];
    $primary_middle_name = $submission_array['ldbase_primary_name'][0]['primary_middle_name'];
    $primary_last_name = $submission_array['ldbase_primary_name'][0]['primary_last_name'];
  
    $possible_matches = $this->nameSearch($primary_first_name, $primary_middle_name, $primary_last_name);
    return $possible_matches;
  }
  
  private function nameSearch($first_name, $middle_name, $last_name) {
    $all_results = array();
    $possible_matches = array();
      
    $first_name_results = $this->personNameQuery($first_name);
    $middle_name_results = $this->personNameQuery($middle_name);
    $last_name_results = $this->personNameQuery($last_name);
    
    if($first_name_results->getResultCount() > 0){
      foreach($first_name_results->getResultItems() as $result) {
        $all_results[] = $result->getId();
      }
    }

    if($middle_name_results->getResultCount() > 0){
      foreach($middle_name_results->getResultItems() as $result) {
        if (in_array($result->getId(), $all_results)) {
          $possible_matches[] = $result->getId(); 
        } else {
          $all_results[] = $result->getId();  
        }
      }
    }
    
    if($last_name_results->getResultCount() > 0){
      foreach($last_name_results->getResultItems() as $result) {
        if (in_array($result->getId(), $all_results)) {
          $possible_matches[] = $result->getId(); 
        }
      }
    }
    
    return $possible_matches;
  }
  
  private function personNameQuery($keyword) {      
    $index = \Drupal\search_api\Entity\Index::load('default_index');
    $query = $index->query();
    
    // Change the parse mode for the search.
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')->createInstance('direct');
    $parse_mode->setConjunction('OR');
    $query->setParseMode($parse_mode);
    
    // Set fulltext search keywords and fields
    $query->keys($keyword);
    $query->setFullTextFields(['person_name_fields_united', 'title']);
    
    // If keyword is empty, make it return an empty result set
    if (empty($keyword)) {
      $query->setOption('limit', 0);
    } 
    
    // Execute the Search and return the results
    $results = $query->execute();
    return $results;
  }
}