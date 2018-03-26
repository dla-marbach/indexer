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

import static java.nio.charset.StandardCharsets.UTF_8;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Types;
import java.util.ArrayList;
import java.util.List;
import java.util.zip.GZIPOutputStream;

import org.apache.commons.io.output.CountingOutputStream;
import org.apache.commons.lang.StringUtils;
import org.apache.tika.Tika;
import org.apache.tika.exception.TikaException;
import org.apache.tika.io.TikaInputStream;
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
		        //BodyContentHandler handler = new BodyContentHandler(20*1024*1024);
		    	
		    	Statement stmtFiles = null;
		    	PreparedStatement insertStmt = null;
		    	PreparedStatement updateStmt = null;
		    	ResultSet rs = null;
		    	try {
		    		String insertSQL = "INSERT INTO info_tika(sessionid, fileid, mimetype, mimeencoding, fullinfo, hascontent, status) "
		    				+ "VALUES( ?, ?, ?, ?, ?, ?, ?)";
		    		insertStmt = conn.prepareStatement(insertSQL);

		    		String updateSQL = "UPDATE `file` SET mtime=NOW() WHERE sessionid=? AND fileid=?";
		    		updateStmt = conn.prepareStatement(updateSQL);
		    		
			    	stmtFiles = conn.createStatement();
			    	
			    	int num;
					do {
				    	num = 0;
				    	List<IndexerFile> fileList = new ArrayList<IndexerFile>();
				    	rs = stmtFiles.executeQuery("SELECT f.sessionid, f.fileid, f.cachefile FROM cachefiles f "
				    			+ "LEFT JOIN info_tika it ON (f.sessionid=it.sessionid AND f.fileid=it.fileid) "
				    			+ "WHERE it.status IS NULL AND f.sessionid="+sessionid
				    			+ " LIMIT 0,1000");
				    	while (rs.next()) {
				    		String filename = rs.getString("cachefile");
				    		Integer fileid = rs.getInt("fileid");
				    		fileList.add(new IndexerFile( sessionid, fileid, filename ));
				    		num++;
				    	}
				    	rs.close();
				    	
				    	for (IndexerFile f : fileList) {
				    		String filename = f.localfile;
				    		Integer fileid = f.fileid;
				    		System.out.println(filename);
				    		File initialFile = new File(filename);
				    		if( ! initialFile.exists()) { continue; }
					        Metadata metadata = new Metadata();		    	
				            try (InputStream stream = TikaInputStream.get(initialFile.toPath(), metadata )) { // new FileInputStream(initialFile)) {
				            	try {
					                Path zipname = Paths.get( filename+".tika.txt.gz" );
					                System.out.println("Writing "+zipname );
					                
					                FileOutputStream fos = new FileOutputStream( zipname.toString() );
					                GZIPOutputStream zip = new GZIPOutputStream(fos);
					                CountingOutputStream cnt = new CountingOutputStream( zip );
					                OutputStreamWriter out = new OutputStreamWriter( cnt, UTF_8 );				              
	
					                BodyContentHandler handler = new BodyContentHandler(out);
				            
				            		parser.parse(stream, handler, metadata);
	
					                String metastr = "";
					                for( String property: metadata.names() ) {
					                	metastr += "["+property+"]: "+StringUtils.abbreviate( metadata.get(property), 250 )+"\n";
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
	//				                String content = handler.toString().trim();
					                
					                int contentSize = cnt.getCount();
					                
				            		out.close();
				            		cnt.close();
				            		zip.close();
					                fos.close();
	
					                insertStmt.setInt( 1, sessionid );
					                insertStmt.setInt( 2, fileid );
					                insertStmt.setString( 3, mimetype );
					                insertStmt.setString( 4, encoding );
					                insertStmt.setString( 5, metastr );
					                insertStmt.setInt( 6, contentSize > 0 ? 1 : 0 );
					                insertStmt.setString( 7,  "ok" );
					                insertStmt.executeUpdate();
					                
					                updateStmt.setInt(1,  sessionid );
					                updateStmt.setInt(2,  fileid );
					                updateStmt.executeUpdate();
					                
					                if( contentSize < 5 ) {
					                	System.out.println( "deleting "+zipname.toString());
					                	Files.delete(zipname);
					                }
	
					                System.out.println(StringUtils.abbreviate(metastr, 300 ));
				            		
				            	}
				            	catch( SQLException e ) {
				            		throw e;
				            	}
				            	catch( Throwable e ) {
				            		System.out.println("Strange Parser ERROR");
				            		e.printStackTrace();
					                insertStmt.setInt( 1, sessionid );
					                insertStmt.setInt( 2, fileid );
					                insertStmt.setNull(3, Types.VARCHAR);
					                insertStmt.setNull(4, Types.VARCHAR);
					                insertStmt.setString( 5, e.getMessage() );
					                insertStmt.setNull(5, Types.INTEGER);
					                insertStmt.setNull(6, Types.INTEGER);
					                insertStmt.setString( 7,  "error" );
					                insertStmt.executeUpdate();
					                updateStmt.setInt(1,  sessionid );
					                updateStmt.setInt(2,  fileid );
					                updateStmt.executeUpdate();
	
				            	}
	//			                return handler.toString();
	//			                return metadata.toString();
				            }			    		
				    	}
		    		} while( num == 1000 );
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
