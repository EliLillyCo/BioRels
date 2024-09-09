#include <fstream>
#include <iostream>
#include "headers/statics/log_policy_interface.h"
#undef NDEBUG /// Active assertion in release
void file_log_policy::open_ostream(const std::string& name)
{
    out_stream->open( name.c_str(), std::ios_base::binary|std::ios_base::out|std::ios_base::app );
    if( !out_stream->is_open() )
    {
        throw(std::runtime_error("LOGGER: Unable to open an output stream"));
    }
}

void file_log_policy::close_ostream()
{
    if( out_stream )
    {
        out_stream->close();
    }
}

void file_log_policy::write(const std::string& msg)
{
    (*out_stream)<<msg<<std::endl;
}

file_log_policy::~file_log_policy()
{
    if( out_stream )
    {
        close_ostream();
    }
}
void file_log_policy::flush(){
    return;
}
void stack_log_policy::open_ostream(const std::string& name)
{
    mExportName=name;
}

void stack_log_policy::close_ostream()
{
    if( out_stream )
    {
        out_stream->close();
    }
}

void stack_log_policy::write(const std::string& msg)
{
    mStream<<msg<<"\n";
}

stack_log_policy::~stack_log_policy()
{
    if( out_stream )
    {
        close_ostream();
    }
}
void stack_log_policy::flush(){

    out_stream->open( mExportName.c_str(), std::ios_base::binary|std::ios_base::out|std::ios_base::app );
    if( !out_stream->is_open() )
    {
        throw(std::runtime_error("LOGGER: Unable to open an output stream"));

    }
    std::cout <<"STREAM:"<<mStream.str()<<std::endl;
    (*out_stream)<<mStream.str()<<std::endl;

}
