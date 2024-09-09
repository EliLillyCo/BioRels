#include <iostream>
#include <sstream>
#include <sys/types.h>
#include <unistd.h>
#include <iomanip>
#include <functional>
#include <pwd.h>
#undef NDEBUG
#include <assert.h>

#include "headers/statics/argcv.h"
#include "headers/statics/protExcept.h"
#include "headers/statics/strutils.h"
#include "headers/statics/logger.h"
using namespace std;
using namespace protspace;




Argcv::~Argcv(){    ELOG_FLUSH;}


Argcv::Argcv(const std::string &pOptionNoValue,
             const std::string &pOptionValue,
             const int &argc,
             const char *argv[],
             std::function<void()> help,
             const bool &withSubJob,
             const size_t &minSize)
    :mJobName(""),
      mProgName(""),
      mProgPath(""),mArgc(argc),

      mWithSubJob(withSubJob),
      mListIniOptNoVal(pOptionNoValue),
      mListIniOptWVal(pOptionValue)
{
    try{
    loadArgs(argc,argv);
    // Ingvar: commented out, could not figure how to handle -v with this in place
    // checkPredefArgs();
    getProgramDir(argv[0]);

    // NOTE: a program may do something useful without a parameter...
    if (argc == 1) {help();ELOG_FLUSH;exit(1);}

    bool gotJob=false;
    for(int i=1;i<argc;++i)
    {
        // Getting parameter:
        const std::string& param=mArgv[i];
        if (isOptWVal(param)){processOption(i);++i;continue;}
        if (isOptNoVal(param)){mUserOptNoVal.push_back(i);continue;}
        if (param=="--help"||param=="-h"){help();exit(0);continue;}
        if (param=="-version"){continue;}
        if (param[0]=='-')
            throw_line("050002",
                       "ARGCV::ARGCV",
                       "Unexpected parameter "+param);

        if (mWithSubJob)
        {
            if (!gotJob) {mJobName=param;gotJob=true;}
            else          mListArgument.push_back(i);
        }else mListArgument.push_back(i);
    }
    if (mListArgument.size() <minSize){help();ELOG_FLUSH
        throw_line("050001",
                   "ARGCV::ARGCV",
                   "not enough arguments");}
}catch(ProtExcept &e)
    {

        throw;
    }
}


void Argcv::loadArgs(   const int &argc,const char *argv[])
{
    for(int i=0;i< argc;++i)
        mArgv.push_back(argv[i]);
}



std::string Argcv::getUserName()const
{
    const struct passwd *pws(getpwuid(geteuid()));
    return std::string(pws->pw_name);

}

void Argcv::getProgramDir(const std::string progPath)
{
    const size_t pos= progPath.find_last_of("/");
    if (pos != string::npos) {
    mProgPath=progPath.substr(0,pos-1);
    mProgName=progPath.substr(pos+1);
    }else
    {
        mProgPath=".";
        mProgName=progPath;
    }
    ELOG_SET(std::string(mProgName+"|"+getUserName()));
}

bool Argcv::isOptWVal(const std::string& param)const
{
    return mListIniOptWVal.find(" " + param + " ") != string::npos;
}


bool Argcv::isOptNoVal(const std::string& param)const
{
    return mListIniOptNoVal.find(" " + param + " ") != string::npos;
}


void Argcv::processOption(const int& i)
{
    if (i+1==mArgc)
        throw_line("050101",
                   " Argcv::processOption",
                   "Expected value for option "+mArgv[i]+". Got nothing.");
    const std::string& paramVal=mArgv[i+1];
    if (isOptNoVal(paramVal)||
        isOptWVal(paramVal))
        throw_line("050102",
                   " Argcv::processOption",
                   "Expected value for option "+mArgv[i]+". Got parameter "+paramVal);

    mUserOpt.insert(std::make_pair(i,i+1));

}

bool Argcv::hasOption(const std::string& param)const
{

    for (size_t i=0;i<mUserOptNoVal.size();++i)
    {
        const size_t& pos=mUserOptNoVal.at(i);
        assert(pos<mArgv.size());
        if (mArgv.at(pos)==param)return true;
    }

    return false;
}

bool Argcv::getOptArg(const std::string& name,
                        std::string&value) const
{
    for(auto it=mUserOpt.begin();it!=mUserOpt.end();++it)
    {
        const std::string& head=mArgv.at((*it).first);


        if (head!=name)continue;
        assert((unsigned)(*it).second< mArgv.size());
        value = mArgv.at((*it).second);
        return true;
    }
    return false;

}

bool Argcv::getOptArg(const std::string& name,
                        size_t&value) const
 {
    for(auto it=mUserOpt.begin();it!=mUserOpt.end();++it)
    {
        const std::string& head=mArgv.at((*it).first);

        if (head!=name)continue;
        assert((unsigned)(*it).second< mArgv.size());
        value = atoi(mArgv.at((*it).second).c_str());
        return true;
    }
    return false;

}

bool Argcv::getOptArg(const std::string& name,
                        short&value) const
 {
    for(auto it=mUserOpt.begin();it!=mUserOpt.end();++it)
    {
        const std::string& head=mArgv.at((*it).first);

        if (head!=name)continue;
        assert((unsigned)(*it).second< mArgv.size());
        value = atoi(mArgv.at((*it).second).c_str());
        return true;
    }
    return false;

}


bool Argcv::getOptArg(const std::string& name,
                        double&value) const
{
    for(auto it=mUserOpt.begin();it!=mUserOpt.end();++it)
    {
        const std::string& head=mArgv.at((*it).first);

        if (head!=name)continue;
        assert((unsigned)(*it).second< mArgv.size());
        value = atof(mArgv.at((*it).second).c_str());
        return true;
    }
    return false;

}

const std::string& Argcv::getArg(const size_t& pos)const
{
    if (pos >= mListArgument.size())
        throw_line("050201",
                   "Argcv::getArg",
                   "Position above number of arguments");
    const size_t& posi=mListArgument.at(pos);
            assert(posi < mArgv.size());
    return mArgv.at(posi);
}

void Argcv::checkPredefArgs()const
{
    static const std::string preUsed=" -h --help --verbose -version ";
    for(const auto& arg:mArgv)
    {
        assert(preUsed.find(" " + arg+ " ") == string::npos);
    }
}
