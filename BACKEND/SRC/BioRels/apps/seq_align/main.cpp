#include <iostream>
#include "headers/statics/argcv.h"
#include "headers/statics/protExcept.h"
#include "headers/sequence/seqstd.h"
#include "headers/sequence/seqalign.h"
#include "headers/sequence/seqpair.h"
#include "headers/sequence/seqnucl.h"
void help()
{
    std::cout <<"SMITH-WATERMAN Sequence alignment\n"
              <<"Usage: seq_align  REF_FILE COMP_FILE\n"
              <<"Usage: seq_align -i -rn RNAME -cn CNAME REF_SEQ COMP_SEQ\n"
              <<"\n"
              <<" REF_FILE:  Reference sequence (in Fasta format)\n"
              <<" COMP_FILE: Comparison sequence (in Fasta format)\n"
              <<"OPTION: \n"
              <<" -n : Nucleotide sequence\n"
              <<" -i : Inline sequence instead of files\n"
              <<" -rn : Reference sequence name \n"
              <<" -rn : Compared sequence name \n";


}
int runProtAlignment(protspace::Argcv& args)
{
    const std::string& ref (args.getArg(0));
    const std::string& comp(args.getArg(1));
    const bool isInline(args.hasOption("-i"));
    if (args.hasOption("-sbl"))protspace::SeqAlign::setAltPath("/biodata/databases/blast/data/");
    std::string rName(""),cName("");
    args.getOptArg("-rn",rName);
    args.getOptArg("-cn",cName);
    protspace::Sequence sqR,sqC;

    if (isInline)
    {
        sqR.setName(rName);
        sqR.loadSequence(ref);
        sqC.setName(cName);
        sqC.loadSequence(comp);
    }
    else {
        sqR.loadFastaFile(ref);
        sqC.loadFastaFile(comp);
    }

    protspace::SeqAlign SA(sqR,sqC);
    protspace::SeqPairAlign SPA(sqR,sqC);
    SA.align(SPA);
     if (args.hasOption("-id"))
     {
         std::cout <<SPA.getIdentity()<<"\t"<<SPA.getSimilarity()<<"\t"<<SPA.getIdentityCommon()<<"\t"<<SPA.getSimilarityCommon()<<"\t"<<SPA.getScore()<<"\t"<<sqR.getName()<<"\t"<<sqC.getName()<<"\n";
     }
    else if (args.hasOption("-all"))
            {
         std::cout <<SPA.printLine()<<"\n";
          std::cout <<SPA.getIdentity()<<"\t"<<SPA.getSimilarity()<<"\t"<<SPA.getIdentityCommon()<<"\t"<<SPA.getSimilarityCommon()<<"\t"<<SPA.getScore()<<"\t"<<sqR.getName()<<"\t"<<sqC.getName()<<"\n";
     }else std::cout <<SPA.printLine()<<"\n";

return 0;
}


int runNuclAlignment(protspace::Argcv& args)
{
    const std::string& ref (args.getArg(0));
    const std::string& comp(args.getArg(1));
    const bool isInline(args.hasOption("-i"));
    if (args.hasOption("-sbl"))protspace::SeqAlign::setAltPath("/biodata/databases/blast/data/");
    std::string rName(""),cName("");
    args.getOptArg("-rn",rName);
    args.getOptArg("-cn",cName);
    protspace::SeqNucl sqR,sqC;

    if (isInline)
    {
        sqR.setName(rName);
        sqR.loadSequence(ref);
        sqC.setName(cName);
        sqC.loadSequence(comp);
    }
    else {
        sqR.loadFastaFile(ref);
        sqC.loadFastaFile(comp);
    }

    protspace::SeqAlign SA(sqR,sqC,false);
    protspace::SeqPairAlign SPA(sqR,sqC,false);
    SA.align(SPA);
     if (args.hasOption("-id"))
     {
         std::cout <<SPA.getIdentity()<<"\t"<<SPA.getSimilarity()<<"\t"<<SPA.getIdentityCommon()<<"\t"<<SPA.getSimilarityCommon()<<"\t"<<SPA.getScore()<<"\t"<<sqR.getName()<<"\t"<<sqC.getName()<<"\n";
     }
    else if (args.hasOption("-all"))
            {
         std::cout <<SPA.printLine()<<"\n";
          std::cout <<SPA.getIdentity()<<"\t"<<SPA.getSimilarity()<<"\t"<<SPA.getIdentityCommon()<<"\t"<<SPA.getSimilarityCommon()<<"\t"<<SPA.getScore()<<"\t"<<sqR.getName()<<"\t"<<sqC.getName()<<"\n";
     }else std::cout <<SPA.printLine()<<"\n";
return 0;
}

int main(const int argc, const char* argv[])
{
    try {
        protspace::Argcv args(" -sbl -i -id -all -n "," -rn -cn ",argc,argv,help,false,2);
        const bool isNucl(args.hasOption("-n"));
        if (isNucl) return runNuclAlignment(args);
        else return runProtAlignment(args);

    } catch (ProtExcept &e) {
        std::cerr<<e.toString();
        return EXIT_FAILURE;

    }

    return EXIT_SUCCESS;
}
