#include <iostream>
#include "cpdtodb.h"
#include "gc3tk.h"
#include"headers/statics/protExcept.h"
#include "headers/statics/logger.h"
void help()
{
 std::cout <<"CpdToDB [INTERNAL_OPTIONS] [EXTERNAL_OPTIONS] list\n"
           <<" EXTERNAL OPTIONS:\n"
           <<" -c  [FILE] : Cif File for external structures\n"
           <<" -exF [FILE] : List of external structure name to process\n"
           <<" -exS [STREAM] : Stream of external structure name to process\n"
           <<" -exD: Let the program ask the database\n"
           <<" OTHER OPTIONS : \n"
           <<" -v : Verbose\n"
           <<" list : List fo xray structure to process\n";
}



int main(const int argc, const char* argv[])
{
    try
    {

        protspace::Argcv args(" -v -inD  -noV "," -exD -oB -oSDF -oH -inF -inS -exF -exS -c -oXML ",argc,argv,help,false,0);
        CpdToDB cpdDB(args);
        cpdDB.run();
    LOG("END OF RUN");
    LOG_FLUSH
    }catch(ProtExcept &e)
    {
            LOG_ERR(e.getDescription());LOG_FLUSH
            ELOG_ERR(e.getDescription());ELOG_FLUSH
                    std::cout<<"FAILURE"<<std::endl;
        return 1;
    }
    std::ofstream ofs("finish");
    if (ofs.is_open())
    {
    ofs<<"CLOSE"<<std::endl;
    ofs.close();
    }

    ELOG_FLUSH
            ELOG("SUCCESS");
            LOG("SUCCESS");
    LOG_FLUSH
            std::cout<<"SUCCESS"<<std::endl;
    return 0;
}


