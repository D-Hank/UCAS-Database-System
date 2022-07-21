#include <iostream>
#include <vector>  
#include <string>  
#include <stdio.h>  
#include <stdlib.h> 

#include <cgicc/CgiDefs.h> 
#include <cgicc/Cgicc.h> 
#include <cgicc/HTTPHTMLHeader.h> 
#include <cgicc/HTMLClasses.h>  
#include <postgresql/libpq-fe.h>

using namespace std;
using namespace cgicc;

void exitNicely(PGconn* conn)
{ PQfinish(conn); exit(1);}

int main ()
{
   Cgicc formData;

    PGconn* conn = PQconnectdb("dbname=tpch user=dbms password=dbms");
    if (PQstatus(conn) != CONNECTION_OK) {
      fprintf(stderr, "Connection to db failed: %s\n", PQerrorMessage(conn));
      exitNicely(conn);
    }

    PGresult *res = PQexec(conn, "select r_regionkey, r_name from region");

    ExecStatusType status= PQresultStatus(res);
    if ((status!=PGRES_TUPLES_OK)&&(status!=PGRES_SINGLE_TUPLE)){
      fprintf(stderr, "failed execution: %s\n", PQresultErrorMessage(res));
      exitNicely(conn);
    }
    int num_rows = PQntuples(res);
    int num_cols = PQnfields(res);
   
   cout << "Content-type:text/html\n\n";
   cout << "<html>\n";
   cout << "<head>\n";
   cout << "<title>The Final Practice</title>\n";
   cout << "</head>\n";
   cout << "<body>\n";

   cout << "<table border=\"1\">\n";
    cout<<"<tr>";
    for(int i=0;i<num_cols; i++)
    {
        cout<<"<td>";
        cout<<PQfname(res, i);
        cout<<"</td>";
    }
    cout<<"</tr>";

char* val;
for(int i=0;i<num_rows;i++)
{
    cout<<"<tr>";
    for(int j=0;j<num_cols; j++)
    {
        val=PQgetvalue(res, i, j);
        cout<<"<td>";
        cout<<val;
        cout<<"</td>";
    }
    cout<<"</tr>";
}
    

    cout<<"</table>\n";
   cout << "</body>\n";
   cout << "</html>\n";
   PQclear(res);
   PQfinish(conn);
   return 0;
}