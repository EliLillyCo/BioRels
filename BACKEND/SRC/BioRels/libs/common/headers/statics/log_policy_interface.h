#ifndef LOG_POLICY_INTERFACE_H
#define LOG_POLICY_INTERFACE_H
#include <memory>
#include <fstream>
#include <sstream>
class log_policy_interface
{
public:
        virtual void		open_ostream(const std::string& name) = 0;
        virtual void		close_ostream() = 0;
        virtual void		write(const std::string& msg) = 0;
        virtual void        flush() =0;
    virtual ~log_policy_interface(){}

};

class file_log_policy : public log_policy_interface
{
        std::unique_ptr< std::ofstream > out_stream;
public:
        file_log_policy() : out_stream( new std::ofstream ) {}
        void open_ostream(const std::string& name);
        void close_ostream();
        void write(const std::string& msg);
        void flush();
        ~file_log_policy();
};

class stack_log_policy : public log_policy_interface
{
        std::ostringstream mStream;
        std::string mExportName;
        std::unique_ptr< std::ofstream > out_stream;
public:
        stack_log_policy() :mExportName(""), out_stream( new std::ofstream )  {}
        void open_ostream(const std::string& name);
        void close_ostream();
        void write(const std::string& msg);
        void flush();
        ~stack_log_policy();
};
#endif // LOG_POLICY_INTERFACE_H

