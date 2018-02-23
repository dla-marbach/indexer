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

package org.objectspace.maven.IndexerTika;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Types;
import java.util.zip.GZIPOutputStream;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.DefaultParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.Option;
import org.apache.commons.cli.Options;
import org.apache.commons.cli.ParseException;
import org.apache.tika.Tika;
import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.AutoDetectParser;
import org.apache.tika.sax.BodyContentHandler;
import org.apache.tika.sax.ToXMLContentHandler;
import org.xml.sax.ContentHandler;
import org.xml.sax.SAXException;

/**
 * Hello world!
 *
 */
public class App 
{
    public static void main( String[] args )
    {
			try {
				String dsn = args[0];
				Integer sessionid = Integer.decode(args[1]);
				
		    	Class.forName("com.mysql.cj.jdbc.Driver").newInstance();
		    	Connection conn = DriverManager.getConnection(dsn);

		    	AutoDetectParser parser = new AutoDetectParser();
		        BodyContentHandler handler = new BodyContentHandler(20*1024*1024);
		        Metadata metadata = new Metadata();		    	
		    	
		    	Statement stmtFiles = null;
		    	PreparedStatement replaceStmt = null;
		    	ResultSet rs = null;
		    	try {
		    		String replaceSQL = "REPLACE INTO info_tika VALUES( ?, ?, ?, ?, ?, ?, ?)";
		    		replaceStmt = conn.prepareStatement(replaceSQL);
		    		
			    	stmtFiles = conn.createStatement();
			    	rs = stmtFiles.executeQuery("SELECT * FROM cachefiles WHERE sessionid="+sessionid);
			    	while (rs.next()) {
			    		String filename = rs.getString("cachefile");
			    		Integer fileid = rs.getInt("fileid");
			    		System.out.println(filename);
			    		File initialFile = new File(filename);
			    		if( ! initialFile.exists()) { continue; } 
			            try (InputStream stream = new FileInputStream(initialFile)) {
			            	try {
			            		parser.parse(stream, handler, metadata);
				                String metastr = "";
				                for( String property: metadata.names() ) {
				                	metastr += "["+property+"]: "+metadata.get(property)+"\n";
				                }
				                metastr = metastr.trim();
				                
				                String mimetype = metadata.get("Content-Type");
				                int spaceIndex = mimetype.indexOf( " " );
				                if( spaceIndex != -1 ) {
				                	mimetype = mimetype.substring(0, spaceIndex-1);
				                }
				                String encoding = metadata.get("Content-Encoding");
				                /*
				                String content = handler.toString()
				                		.replaceAll("[^\\p{L}\\p{N}.\n\r,*()?!\"$%':; ]", " " )
				                		.replaceAll( "\\s+", " " )
				                		.trim();
				                */
				                String content = handler.toString().trim();
				                
				                replaceStmt.setInt( 1, sessionid );
				                replaceStmt.setInt( 2, fileid );
				                replaceStmt.setString( 3, mimetype );
				                replaceStmt.setString( 4, encoding );
				                replaceStmt.setString( 5, metastr );
				                replaceStmt.setInt( 6, content.length() > 0 ? 1 : 0 );
				                replaceStmt.setString( 7,  "done" );
				                replaceStmt.executeUpdate();

				                if( content.length() > 0 ) {
					                String zipname = filename+".tika.txt.gz";
					                System.out.println("Writing "+zipname );
					                FileOutputStream os = new FileOutputStream( zipname );
					                GZIPOutputStream zip = new GZIPOutputStream(os);
					                zip.write(content.getBytes());
					                zip.close();
					                os.close();
				                }
				                
				                System.out.println(metastr);
			            		
			            	}
			            	catch( Throwable e ) {
			            		System.out.println("Strange Parser ERROR");
			            		e.printStackTrace();
				                replaceStmt.setInt( 1, sessionid );
				                replaceStmt.setInt( 2, fileid );
				                replaceStmt.setNull(3, Types.VARCHAR);
				                replaceStmt.setNull(4, Types.VARCHAR);
				                replaceStmt.setString( 5, e.getMessage() );
				                replaceStmt.setNull(5, Types.INTEGER);
				                replaceStmt.setNull(6, Types.INTEGER);
				                replaceStmt.setString( 7,  "error" );
				                replaceStmt.executeUpdate();

			            	}
//			                return handler.toString();
//			                return metadata.toString();
			            }			    		
			    	}
		    	
		    	}
		    	catch (SQLException ex){
		    	    // handle any errors
		    	    System.out.println("SQLException: " + ex.getMessage());
		    	    System.out.println("SQLState: " + ex.getSQLState());
		    	    System.out.println("VendorError: " + ex.getErrorCode());
		    	}
		    	finally {

		    	    if (rs != null) {
		    	        try {
		    	            rs.close();
		    	        } catch (SQLException sqlEx) { } // ignore

		    	        rs = null;
		    	    }

		    	    if (stmtFiles != null) {
		    	        try {
		    	        	stmtFiles.close();
		    	        } catch (SQLException sqlEx) { } // ignore

		    	        stmtFiles = null;
		    	    }
		    	}
		    	
			} catch (IOException | InstantiationException | IllegalAccessException | ClassNotFoundException e) {
				e.printStackTrace();
			} catch (SQLException e) {
				System.out.println("SQLException: " + e.getMessage());
			    System.out.println("SQLState: " + e.getSQLState());
			    System.out.println("VendorError: " + e.getErrorCode());
			}
    }
    
    public static String parseToStringExample() throws IOException, SAXException, TikaException {
        Tika tika = new Tika();
        File initialFile = new File("C:/temp/LME_Bizet Carmen Habanerax - Alt-Saxophon 1 & 2 Es.pdf");
        try (InputStream stream = new FileInputStream(initialFile)) {
            return tika.parseToString(stream);
        }
    }    
    
    public static String parseExample() throws IOException, SAXException, TikaException {
        AutoDetectParser parser = new AutoDetectParser();
        BodyContentHandler handler = new BodyContentHandler();
        Metadata metadata = new Metadata();
        File initialFile = new File("C:/temp/LME_Bizet Carmen Habanerax - Alt-Saxophon 1 & 2 Es.pdf");
        try (InputStream stream = new FileInputStream(initialFile)) {
            parser.parse(stream, handler, metadata);
//            return handler.toString();
            return metadata.toString();
        }
    }
    
    public static String parseToPlainText() throws IOException, SAXException, TikaException {
        BodyContentHandler handler = new BodyContentHandler();
     
        AutoDetectParser parser = new AutoDetectParser();
        Metadata metadata = new Metadata();
        File initialFile = new File("C:/temp/LME_Bizet Carmen Habanerax - Alt-Saxophon 1 & 2 Es.pdf");
        try (InputStream stream = new FileInputStream(initialFile)) {
            parser.parse(stream, handler, metadata);
            return handler.toString();
        }
    }
    
    public static String parseToHTML() throws IOException, SAXException, TikaException {
        ContentHandler handler = new ToXMLContentHandler();
     
        AutoDetectParser parser = new AutoDetectParser();
        Metadata metadata = new Metadata();
        File initialFile = new File("C:/temp/LME_Bizet Carmen Habanerax - Alt-Saxophon 1 & 2 Es.pdf");
        try (InputStream stream = new FileInputStream(initialFile)) {
            parser.parse(stream, handler, metadata);
            return handler.toString();
        }
    }    
}
