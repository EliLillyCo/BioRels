#ifndef ARGCV_H
#define ARGCV_H

#include <string>
#include <map>
#include <vector>
#include <functional>

namespace protspace
{
class Argcv
{

    /**
     * \brief mJobName
     */
    std::string mJobName;

    std::string mProgName;
    std::string mProgPath;
    std::vector<std::string> mArgv;
     int mArgc;
    bool mWithSubJob;

    ///
    /// \brief List of options that does not expect a value
    ///
    const std::string mListIniOptNoVal;

    ///
    /// \brief List of options that does expect a value
    ///
    const std::string mListIniOptWVal;



    ///
    /// \brief Process the first input value to get program name and path
    /// \param progPath
    ///
    void getProgramDir(const std::string progPath);
    bool isOptWVal(const std::string& param)const;
    void processOption(const int &i);
    bool isOptNoVal(const std::string& param)const;
    void loadArgs(   const int &argc,const char *argv[]);


    std::map<int,int> mUserOpt;
    std::vector<int> mUserOptNoVal;

    /**
     * @brief mListArgument
     */
    std::vector<int> mListArgument;





    std::vector<std::string> mResidueList;

    std::vector<std::string> mFullCommonNames;


    void checkPredefArgs() const;
    std::string getUserName()const;
public:

    Argcv(const std::string &pOptionNoValue,
          const std::string &pOptionValue,
          const int& argc,
          const char* argv[],
          std::function<void()> help,
          const bool &withSubJob=true,
          const size_t& minSize=0
          );


    size_t numArgs()const {return mListArgument.size();}

    bool hasOption(const std::string& param)const;

    bool getOptArg(const std::string& name,
                         std::string&value) const;
    bool getOptArg(const std::string& name,
                            double&value) const;
    bool getOptArg(const std::string& name,
                            short &value) const;
    bool getOptArg(const std::string& name,
                            size_t&value) const;
    void loadXML(const std::string& xmldata);

    void processReadOption(const int& i);


    /**
     * @brief getArg
     * @param pos
     * @return
     * @throw 050201        Argcv::getArg       Position above number of arguments
     */
    const std::string& getArg(const size_t& pos)const;
    const std::string& getJobName()const{return mJobName;}

    const std::vector<std::string>& getListResidues() const {return mResidueList;}

    const std::vector<std::string>& getListEntries()const {return mFullCommonNames;}
    ~Argcv();
};
}
#endif // ARGCV_H

