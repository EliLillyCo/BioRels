#include <ctime>
#include <fstream>
#include <stdexcept>
#include <iostream>
#include "headers/statics/logger.h"
#include "headers/statics/delmanager.h"
#include "headers/statics/delsingleton.h"
#undef NDEBUG /// Active assertion in release



Logger::LoggerPtr& Logger::get_instance()
{
    static LoggerPtr the_singleton(new Logger);
    return the_singleton;
}
Logger::Logger()
{
    new protspace::TDelOrderSing<Logger>(this,protspace::DelSingleton(0));
    LOG_OUT mExec(FILE_L,"execution.log");
    mLogs.push_back(mExec);
    LOG_OUT mVerb(FILE_L,"verbose.log");
    mLogs.push_back(mVerb);
    LOG_OUT mStdO(STDOUT,"stdo");
    mLogs.push_back(mStdO);
}


Logger::~Logger()
{
}
std::string Logger::getTime()
{

    struct tm* info;
    char buffer[120];
    time_t raw_time;

    time( & raw_time );
    info =localtime(&raw_time);
    strftime(buffer,120,"%d|%m|%Y|%I|%M|%S",info);
    std::string str(buffer);
    return str;
}


std::string Logger::getLogHeader(LOG_OUT& entry)
{
    std::stringstream header;

    header.str("");
    header.fill('0');
    header.width(7);
    header << entry.nLine++ <<"|"<<getTime()<<"|";

    header.fill('0');
    header.width(7);
    header <<clock()<<"|  |";

    return header.str();
}

void Logger::print( const std::string& name,
                    const LOG_LEVEL& severity,
                    const std::string& data)
{
    write_mutex.lock();
    std::stringstream log_stream;
    log_stream<<mPreHead;
    switch( severity )
    {
    case LOG_LEVEL::debug:
        log_stream<<"|DEBUG|";
        break;
    case LOG_LEVEL::trace:
        log_stream<<"|TRACE|";
        break;
    case LOG_LEVEL::verbose:
        log_stream<<"|VERBOSE|";
        break;
    case LOG_LEVEL::issue:
        log_stream<<"|ISSUE|";
        break;
    case LOG_LEVEL::error:
        log_stream<<"|ERROR|";
        break;
    case LOG_LEVEL::warning:
        log_stream<<"|WARNING|";
        break;
    case LOG_LEVEL::run:
        log_stream<<"|RUN|";
        break;
    };

    for(LOG_OUT& entry:mLogs)
    {
        if (name != entry.pFile)continue;
        log_stream<<getLogHeader(entry)<<"|"<<data<<"\n";
        if (entry.type==STDOUT)std::cout<<log_stream.str();
        entry.oss<<log_stream.str();
    }
    write_mutex.unlock();

}
void Logger::disable(const std::string& name)
{write_mutex.lock();
    for(LOG_OUT& entry:mLogs)
    {
        if (name != entry.pFile)continue;
        entry.enabled=false;

    }
      write_mutex.unlock();
}
void Logger::flush(const std::string& log_name)
{write_mutex.lock();
    for(LOG_OUT& entry:mLogs)
    {
        if (log_name != entry.pFile)continue;
        if (!entry.enabled)continue;
        if (entry.type==STDOUT){std::cout << entry.oss.str();continue;}
        std::ofstream ofs(entry.pFile,std::ios::app);
        if (!ofs.is_open())return;
        ofs<< entry.oss.str();
        ofs.close();
        entry.oss.str("");
    }

    write_mutex.unlock();
}

void Logger::set(const std::string &name, const std::string& pHead)
{
    mPreHead=pHead;
    print(name,LOG_LEVEL::run,pHead);
}
