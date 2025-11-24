<?php

namespace Drupal\origins_pat\Enum;
enum PatReport: string {
  case LINKS = 'public://pat_links.csv';
  case SELF_REFERENCES = 'public://pat_self_references.csv';
  case DEAD_LINKS = 'public://pat_dead_links.csv';
}
