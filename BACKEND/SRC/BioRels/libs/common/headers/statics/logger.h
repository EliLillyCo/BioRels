#ifndef LOGGER_H
#define LOGGER_H

#include <string>
#include <vector>
#include <mutex>
#include <memory>
#include <sstream>
enum LOG_LEVEL
{
  debug=1,
  trace,
  verbose,
  error,
  issue,
    warning,
    run
};
enum LOG_TYPE
{
    STDOUT=0,
    FILE_L
};
struct LOG_OUT
{
    LOG_TYPE type;
    std::string pFile;
    std::ostringstream oss;
    size_t nLine;
    bool enabled;
    LOG_OUT(const LOG_TYPE& p,const std::string& f):type(p),pFile(f),nLine(0),enabled(true){}
    LOG_OUT(const LOG_OUT& p):type(p.type),pFile(p.pFile),nLine(p.nLine),enabled(p.enabled){oss<<p.oss.str();}
};
class Logger
{
    typedef std::unique_ptr<Logger> LoggerPtr;
    friend class std::unique_ptr<Logger>;
    std::vector<LOG_OUT> mLogs;
    std::string mPreHead;
protected:
    Logger(const Logger&) {}
    Logger& operator=(const Logger&);
    Logger();
    std::string getLogHeader(LOG_OUT &entry);
    std::string getTime();
std::mutex write_mutex;
    static LoggerPtr& get_instance();

public:
    ~Logger();
    void flush(const std::string& log_name);
    static Logger& Instance(){return *get_instance();}
    static const Logger& const_instance(){return Instance();}
static void destroy_instance(){delete get_instance().release();}
void print( const std::string& name,
            const LOG_LEVEL& severity,
            const std::string& data);
void disable(const std::string& name);
void set(const std::string& name, const std::string& pHead);
};

#define LOG(X)      Logger::Instance().print("verbose.log",LOG_LEVEL::trace,  X);
#define LOG_V(X)    Logger::Instance().print("verbose.log",LOG_LEVEL::verbose,X);
#define LOG_ERR(X)  Logger::Instance().print("verbose.log",LOG_LEVEL::error,  X);
#define LOG_WARN(X) Logger::Instance().print("verbose.log",LOG_LEVEL::warning,X);
#define LOG_FLUSH   Logger::Instance().flush("verbose.log");
#define LOG_DISABLE Logger::Instance().disable("verbose.log");
#define ELOG(X)     Logger::Instance().print("execution.log",LOG_LEVEL::trace,X);
#define ELOG_SET(X) Logger::Instance().set("execution.log",X);
#define ELOG_ERR(X) Logger::Instance().print("execution.log",LOG_LEVEL::error,X);
#define ELOG_FLUSH  Logger::Instance().flush("execution.log");
#endif // LOGGER_H
