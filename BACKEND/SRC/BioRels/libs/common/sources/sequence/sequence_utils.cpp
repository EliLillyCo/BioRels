#include <fstream>
#include "headers/sequence/sequence_utils.h"
#include "headers/statics/strutils.h"
#undef NDEBUG /// Active assertion in release
namespace protspace
{

void loadSequenceAlignment(const GroupList<SequenceChain> &mListSequences,
                           const std::string& pFile,
                          std::vector<std::string> & pListSeqs,
                          std::vector<short>& pListchains)
{
    std::ifstream ifs(pFile);
    if (!ifs.is_open())
        throw_line("XXXXXXX",
                   "MultiChainSeq::loadAligments",
                   "Cannot open sequence alignment");
    std::string line,name="",sequence="";int currMole=-1;
    const size_t nChains(mListSequences.size());
    pListSeqs.clear();
    for(size_t i=0;i<nChains;++i)pListSeqs.push_back("");
    while (!ifs.eof())
    {
        getline(ifs,line);

        if (line.substr(0,1)==">")
        {
            if (currMole!= -1)pListSeqs.at(currMole)=sequence;
            sequence=""; currMole=-1;
            name=line.substr(1);

            for(size_t i=0;i<nChains;++i)
            {
                SequenceChain& seqIniChain = mListSequences.get(i);
                const std::string& moleName=seqIniChain.getMoleName();
                const size_t pos(name.find(moleName));
                if (pos== std::string::npos)continue;
                currMole = i;
                if (pos==moleName.length()){pListchains.push_back(-2);continue;}
                const std::string seqChain(name.substr(pos+moleName.length()+1));
                if (seqIniChain.getChain().getName() == seqChain)
                {
                    pListchains.push_back(i);
                }
                else pListchains.push_back(-1);
            }
        }
        else if (line.empty() || line.at(0)==' ')
        {
            if (currMole != -1)     pListSeqs.at(currMole)=sequence;
            sequence=""; name=""; currMole=-1;
        }
        else sequence += removeSpaces(line);

    }
    ifs.close();
}

void loadSequenceAlignment( const std::string& pFile,
                          std::vector<std::string> & pListSeqs,
                            std::vector<std::string>& listNames)
{
    std::ifstream ifs(pFile);
    if (!ifs.is_open())
        throw_line("XXXXXXX",
                   "MultiChainSeq::loadAligments",
                   "Cannot open sequence alignment");
    std::string line,name="",sequence="";
    pListSeqs.clear();
    while (!ifs.eof())
    {
        getline(ifs,line);

        if (line.substr(0,1)==">")
        {
           if (!sequence.empty())
           {
               pListSeqs.push_back(sequence);
               listNames.push_back(name);
               std::cout <<"ADDING "<<name<<" " << pListSeqs.size()<<" " << listNames.size()<<std::endl;
           }
            sequence="";
            name=line.substr(1);


        }
        else if (line.empty() || line.at(0)==' ')
        {
            if (!sequence.empty())
            {
                pListSeqs.push_back(sequence);
                listNames.push_back(name);
                std::cout <<"ADDING "<<name<<" " << pListSeqs.size()<<" " << listNames.size()<<std::endl;
            }
            sequence=""; name="";
        }
        else sequence += removeSpaces(line);

    }
    ifs.close();

    if (sequence.empty())return;
    pListSeqs.push_back(sequence);listNames.push_back(name);
}

}
