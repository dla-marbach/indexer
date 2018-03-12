package org.objectspace.maven.IndexerTika;

public final class IndexerFile {
	public Integer sessionid;
	public Integer fileid;
	public String localfile; 
	
	public IndexerFile( Integer _sessionid, Integer _fileid, String _localfile ) {
		this.sessionid = _sessionid;
		this.fileid = _fileid;
		this.localfile = _localfile;
	}
}
