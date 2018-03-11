<?php

/***********************************************************
 * indexer is a software for supporting the review process of unstructured data
 *
 * Copyright © 2012-2018 Juergen Enge (juergen@info-age.net)
 * FHNW Academy of Art and Design, Basel
 * Deutsches Literaturarchiv Marbach
 * Hochschule für Angewandte Wissenschaft und Kunst Hildesheim/Holzminden/Göttingen
 *
 * indexer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * indexer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with indexer.  If not, see <http://www.gnu.org/licenses/>.
 ***********************************************************/
namespace indexer;

class Autoloader {

  public static function register()
  {
    spl_autoload_register(array(new self, 'load'));
  }

  public static function load( $name ) {
    static $solrinc = __DIR__.'/lib/solarium/src';

    if( substr( $name, 0, 8 ) == 'Solarium' ) {
      $fname = $solrinc.str_replace( '\\', '/', substr( $name, strlen( 'Solarium' ))).'.php';
      if( !file_exists( $fname )) throw new \Exception("Kann {$name} - {$fname} nicht laden.");
      require( $fname );
    }
  }
}
