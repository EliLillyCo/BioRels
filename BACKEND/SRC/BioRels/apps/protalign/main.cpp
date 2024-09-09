#include <fstream>
#include "headers/statics/argcv.h"
#include "headers/statics/strutils.h"
#include "toolprotalign.h"
#include "headers/statics/logger.h"

#include "headers/molecule/hetmanager.h"
void help()
{
    std::cout <<"protalign - Protein 3D alignment tool\n"
         <<" Usage : protalign [OPTIONS] RefMole CompMole RefName compName\n"
         <<" RefMole : File of the 3D fixed molecule (PDB/MOL2/SDF)\n"
         <<" CompMole :File of the molecule to be aligned onto RefMole\n"
         <<" RefName : Name of the fixed molecule\n"
         <<" CompName : Name of the mobile molecule\n\n"
         <<" \n######## \n"
         <<" OPTIONS: \n"
         <<" -p [CHAIN_PAIR] : Chain association between the two structures\n"
         <<"   Example : -p A:C_B:D \n"
         <<"       Chain A of RefMole will be aligned to chain C of CompMole\n "
         <<"   AND Chain B of RefMole will be aligned to chain D of CompMole\n"
         <<"   Default : 1st chain of RefMole with 1st chain of CompMole\n"
         <<" -l [RES_LIST] : Limit the 3D alignment by only considering residues in RES_LIST\n "
         <<"    RES_LIST is a file containing the residue id of the RefMole that are desired\n"
         <<"    One residue id per line\n"
         <<" -w : Alignment on the whole protein  \n"
         <<" -t [THRES] : Distance threshold to start considering similar pairs of residues\n"
         <<" -gm : Use Graph Matching to align (Slower but better if conformational changes\n"
         <<" -o [OUT_FILE] : Output the aligned CompMole\n"
         <<" -a [ANALYSIS_FILE] : Output the analysis file\n"
         <<" -f : To use with -l. Force the program to continue even if the residue ID does not exists\n";
}


void proceedMultiple(ToolProtAlign& toolProtAlign,const protspace::Argcv& pArgs)
{
    const std::string pInput(pArgs.getArg(0));
    std::ifstream ifs(pInput);
    if (!ifs.is_open())
        throw_line("2700101",
                   "protalign::proceedMultiple",
                   "Unable to open file "+pInput);
    std::string fInputLine="";
    std::getline(ifs,fInputLine);
    std::vector<std::string> toks;
    protspace::tokenStr(fInputLine,toks," ");
    toolProtAlign.isMultipleAnalysis(true);
    if (toks.size()==2)
    {
        /// First structure as reference structure
        toolProtAlign.setReference(toks.at(0),toks.at(1));


        while(!ifs.eof())
        {
            fInputLine="";std::getline(ifs,fInputLine);
            if (fInputLine=="")break;
            toks.clear();
            protspace::tokenStr(fInputLine,toks," ");
            if (toks.size()!= 2)continue;
            toolProtAlign.setComparison(toks.at(0),toks.at(1));
            toolProtAlign.processPair();
        }

    }
    else
    {
        /// List of pairs to process
        std::cerr <<"Unable to understand such a file"<<std::endl;
    }


    ifs.close();
    ifs.open(pInput);





    ifs.close();
}

int main(const int argc, const char* argv[])
{
    bool flush=false;
    try{

        protspace::Argcv args(" -gm -v  -f -w -ex -ca -m ", " -a -l -p -t -o ",argc,argv,help,false,1);
        std::string value="";
        ToolProtAlign toolProtAlign;
        toolProtAlign.setEnforce(!args.hasOption("-f"));;

        if (args.getOptArg("-a",value))toolProtAlign.setAnalysisFile(value);std::cout <<"A"<<std::endl;;
        if (args.getOptArg("-p",value))toolProtAlign.defineChainPairs(value);std::cout <<"A"<<std::endl;;
        if (args.getOptArg("-o",value))toolProtAlign.setOutFile(value);std::cout <<"A"<<std::endl;;
        if (args.getOptArg("-l",value))toolProtAlign.defineResidueList(value);std::cout <<"A"<<std::endl;;
        if (args.getOptArg("-t",value))toolProtAlign.setMinThreshold(atof(value.c_str()));std::cout <<"A"<<std::endl;;
        if (args.hasOption("-ca"))toolProtAlign.setCA(true);std::cout <<"A"<<std::endl;;
        if (args.hasOption("-gm"))toolProtAlign.setwGMatch(true);std::cout <<"A"<<std::endl;;
        if (args.hasOption("-ex"))toolProtAlign.setSwitch(false);std::cout <<"A"<<std::endl;;
        if (args.hasOption("-v"))flush=true;std::cout <<"A"<<std::endl;;
std::cout << args.numArgs()<<std::endl;
        toolProtAlign.setWhole(args.hasOption("-w"));

        if (args.numArgs()==4) {
            const std::string pRefFile(args.getArg(0));
            const std::string pCompFile(args.getArg(1));
            const std::string pRefName(args.getArg(2));
            const std::string pCompName(args.getArg(3));

            toolProtAlign.setReference(pRefFile,pRefName);
            toolProtAlign.setComparison(pCompFile,pCompName);
            toolProtAlign.processPair();
            if (args.hasOption("-m"))toolProtAlign.printAlignmentMatrix();

        }
        else if (args.numArgs()==1)
        {
            proceedMultiple(toolProtAlign, args);

        }else
            throw_line("2700201",
                       "protalign",
                       "Wrong number of argument");
    }catch (ProtExcept &e){
        ELOG_FLUSH
        std::cerr <<e.toString()<<std::endl;
       if (flush) LOG_FLUSH
        help();
        return 1;}
    if (flush) LOG_FLUSH
ELOG_FLUSH


    return 0;
}
